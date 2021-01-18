<?php

/**
 * Created by vs code.
 * User: nijia <15279663@qq.com>
 * Date: 2021/01/11
 * Time: 上午08:00
 */

namespace PhpRest;

use Psr\Container\ContainerInterface;
use DI\FactoryInterface;
use Invoker\InvokerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\ParameterBag;
use PhpRest\Exception\ExceptionHandlerInterface;
use PhpRest\Exception\ExceptionHandler;
use PhpRest\Exception\BadRequestException;
use PhpRest\Render\ResponseRenderInterface;
use PhpRest\Render\ResponseRender;
use Doctrine\Common\Cache\Cache;
use Doctrine\Common\Cache\ApcuCache;
use Doctrine\Common\Cache\FilesystemCache;

class Application implements ContainerInterface, FactoryInterface, InvokerInterface
{
    /**
     * 创建app对象
     * 
     * @param string|array $conf
     * @return Application
     */
    public static function createDefault($conf = []) 
    {
        $default = [
            // 默认request对象来自 symfony
            Request::class => \DI\factory([Application::class, 'createRequestFromSymfony']),
            // 默认错误处理器
            ExceptionHandlerInterface::class => \DI\create(ExceptionHandler::class),
            // 默认输出处理器
            ResponseRenderInterface::class => \DI\create(ResponseRender::class),
            // 数据库配置
            \Medoo\Medoo::class => \DI\create()->constructor(\DI\get('database'))
        ];

        // // 缓存对象
        // if( function_exists('apcu_fetch') ) {
        //     $default += [ Cache::class => \DI\create(ApcuCache::class) ];
        // } else {
        //     $default += [ Cache::class => \DI\autowire(FilesystemCache::class)->constructorParameter('directory', sys_get_temp_dir()) ];
        // }
        
         // $default += [ Cache::class => \DI\autowire(FilesystemCache::class)->constructorParameter('directory', $_SERVER['DOCUMENT_ROOT'] . '/../cache/') ];
        $default += [ Cache::class => \DI\autowire(\Doctrine\Common\Cache\VoidCache::class) ];

        $builder = new \DI\ContainerBuilder();
        $builder->addDefinitions($default);
        $builder->addDefinitions($conf);
        $builder->useAutowiring(false);
        $builder->useAnnotations(true);
        $container = $builder->build();

        return $container->get(self::class);
    }

    /**
     * 遍历加载物理文件controller
     * 
     * 只会加载 'Controller.php' 结尾的PHP文件
     * 
     * @param string $controllerPath controller所在目录
     * @param string $namespace controller所在命名空间
     */
    public function loadRoutesFromPath($controllerPath, $namespace) 
    {
        $d = dir($controllerPath);
        while (($entry = $d->read()) !== false){
            if ($entry == '.' || $entry == '..') { continue; }
            $path = $controllerPath . '/' . $entry;
            if (is_file($path)) {
                if (substr($entry, -14) === 'Controller.php') {
                    $classPath = $namespace . '\\' . substr($entry, 0, -4);
                    $this->loadRoutesFromClass($classPath);
                }
            } else {
                $this->loadRoutesFromPath($path, $namespace . '\\' . $entry);
            }
        }
        $d->close();
    }

    /**
     * 遍历加载 controller 类
     * 
     * @param string $classPath controller命名空间全路径
     */
    private function loadRoutesFromClass($classPath) 
    {
        try {
            $controller = $this->controllerBuilder->build($classPath);
            foreach ($controller->routes as $actionName => $route) {
                $this->routes[] = [$route->method, $route->uri, $classPath, $actionName];
            }
        } catch (\Throwable $e) {
            $exceptionHandler = $this->get(ExceptionHandlerInterface::class);
            $exceptionHandler->render($e)->send();
            exit;
        }
    }

    /**
     * 解析请求
     */
    public function dispatch() 
    {
        $request = $this->get(Request::class);
        $httpMethod = $request->getMethod();
        $uri = $request->getRequestUri();
        if ($uri === '/favicon.ico') { exit; }

        // Strip query string (?foo=bar) and decode URI
        if (false !== $pos = strpos($uri, '?')) {
            $uri = substr($uri, 0, $pos);
        }
        $uri = rawurldecode($uri);

        $app = $this;
        // 把解析注解收集的信息，注册成FastRoute路由
        $dispatcher = \FastRoute\simpleDispatcher(function(\FastRoute\RouteCollector $r) use($app) {
            foreach($app->routes as $route) {
                list($method, $uri, $classPath, $actionName) = $route;
                $r->addRoute($method, $uri, [$classPath, $actionName]);
            }
        });
        
        // FastRoute匹配当前路由
        $routeInfo = $dispatcher->dispatch($httpMethod, $uri);
        try {
            if ($routeInfo[0] == \FastRoute\Dispatcher::FOUND) {                
                
                $next = function($request) use ($app, $routeInfo) {
                    if (count($routeInfo[2])) { // 支持 path 参数, 规则参考FastRoute
                        $request->attributes->add($routeInfo[2]);
                    }
                    list($classPath, $actionName) = $routeInfo[1];
    
                    $controller = $app->controllerBuilder->build($classPath);
                    $routeInstance = $controller->getRoute($actionName);
                    $routeInstance->hooks = array_merge($controller->hooks, $routeInstance->hooks); // 合并class + method hook
                    return $routeInstance->invoke($app, $request, $classPath, $actionName);
                };

                foreach (array_reverse($app->globalHooks) as $hookName){
                    $next = function($request)use($app, $hookName, $next){
                        return $app->get($hookName)->handle($request, $next);
                    };
                }

                $response = $next($request);
                $response->send();

            } elseif ($routeInfo[0] == \FastRoute\Dispatcher::NOT_FOUND) {
                throw new BadRequestException("{$uri} 访问地址不存在");
            } elseif ($routeInfo[0] == \FastRoute\Dispatcher::METHOD_NOT_ALLOWED) {
                throw new BadRequestException("{$uri} 不支持 {$httpMethod} 请求");
            } else {
                throw new BadRequestException("unknown dispatch return {$routeInfo[0]}");
            }
        } catch (\Throwable $e) {
            $exceptionHandler = $app->get(ExceptionHandlerInterface::class);
            $exceptionHandler->render($e)->send();
        }
    }

    /**
     * impl Psr\Container\ContainerInterface
     */
    public function get($id) 
    {
        return $this->container->get($id);
    }

    /**
     * impl Psr\Container\ContainerInterface
     */
    public function has($id)
    {
        return $this->container->has($id);
    }

    /**
     * impl DI\FactoryInterface
     */
    public function make($name, array $parameters = []) 
    {
        return $this->container->make($name, $parameters);
    }

    /**
     * impl Invoker\InvokerInterface
     */
    public function call($callable, array $parameters = [])
    {
        return $this->container->call($callable, $parameters);
    }

    public static function createRequestFromSymfony()
    {
        $request = Request::createFromGlobals();
        $contentType = $request->headers->get('CONTENT_TYPE');
        $httpMethod  = $request->getMethod();
        if (0 === strpos($contentType, 'application/json') && in_array($httpMethod, ['POST', 'PUT'])) {
            $content = $request->getContent();
            $data = json_decode($request->getContent(), true)?:[];
            $request->request = new ParameterBag($data);
        }
        return $request;
    }
    
    /**
     * @param \string[] $globalHooks
     */
    public function addGlobalHooks($globalHooks)
    {
        $this->globalHooks += $globalHooks;
    }

    /**
     * @Inject
     * @var \PhpRest\Controller\ControllerBuilder
     */
    private $controllerBuilder;

    /** 
     * @Inject
     * @var \DI\Container 
     * */
    private $container;

    /** 
     * 所有路由信息(非 Route 对象)
     * 
     * ?? 是否要缓存，缓存了的话修改代码就不会识别了，但是生产环境中通常又不会修改文件
     * 
     * @var array 
     * */
    private $routes = [];

    /**
     * 全局Hook
     * 
     * @var string[] Hook类全命名空间
     */
    private $globalHooks = [];
}