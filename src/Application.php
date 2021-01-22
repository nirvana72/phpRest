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
use PhpRest\Controller\ControllerBuilder;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ParameterBag;
use PhpRest\Exception\ExceptionHandlerInterface;
use PhpRest\Exception\ExceptionHandler;
use PhpRest\Exception\BadCodeException;
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
    public static function create($conf = [])
    {
        $default = [
            // 默认request对象来自 symfony
            Request::class => \DI\factory([Application::class, 'createRequestFromSymfony']),
            // Response 对象
            Response::class => \DI\create(),
            // 默认错误处理器
            ExceptionHandlerInterface::class => \DI\autowire(ExceptionHandler::class),
            // 默认输出处理器
            ResponseRenderInterface::class => \DI\autowire(ResponseRender::class),
            // 数据库配置
            \Medoo\Medoo::class => \DI\create()->constructor(\DI\get('database'))
        ];

        // 缓存对象
        if( function_exists('apcu_fetch') ) {
            // $default += [ Cache::class => \DI\create(ApcuCache::class) ];
            $default += [ Cache::class => \DI\autowire(\Doctrine\Common\Cache\VoidCache::class) ];
        } else {
            $default += [ Cache::class => \DI\autowire(FilesystemCache::class)->constructorParameter('directory', sys_get_temp_dir()) ];
        }

        $builder = new \DI\ContainerBuilder();
        $builder->addDefinitions($default);
        $builder->addDefinitions($conf);
        $builder->useAutowiring(false);
        $builder->useAnnotations(true);
        $container = $builder->build();
        self::$_instance = $container->get(self::class);
        return self::$_instance;
    }

    /**
     * 遍历加载物理文件controller
     * 
     * 只会加载 'Controller.php' 结尾的PHP文件
     * 
     * @param string $controllerPath controller所在目录
     * @param string $namespace controller所在命名空间
     */
    public function scanRoutesFromPath($controllerPath, $namespace) 
    {
        $d = dir($controllerPath);
        while (($entry = $d->read()) !== false){
            if ($entry == '.' || $entry == '..') { continue; }
            $path = $controllerPath . '/' . $entry;
            if (is_file($path)) {
                // if ($entry === 'IndexController.php') {
                if (substr($entry, -14) === 'Controller.php') {
                    $classPath = $namespace . '\\' . substr($entry, 0, -4);
                    $this->scanRoutesFromClass($classPath);
                }
            } else {
                $this->scanRoutesFromPath($path, $namespace . '\\' . $entry);
            }
        }
        $d->close();
    }

    /**
     * 遍历加载 controller 类
     * 
     * @param string $classPath controller命名空间全路径
     */
    private function scanRoutesFromClass($classPath) 
    {
        try {
            $controller = $this->get(ControllerBuilder::class)->build($classPath);
            foreach ($controller->routes as $actionName => $route) {
                $this->routes[] = [$route->method, $route->uri, [$classPath, $actionName]];
            }
            $this->controllers[] = $classPath;
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
        if ($httpMethod == 'OPTIONS') {
            $response = $this->make(Response::class);
            $response->setStatusCode(200);
            $response->send();
            exit;
        }

        $uri = $request->getRequestUri();
        if (false !== $pos = strpos($uri, '?')) {
            $uri = substr($uri, 0, $pos);
        }
        $uri = rawurldecode($uri);

        // 把解析注解收集的信息，注册成FastRoute路由
        $dispatcher = \FastRoute\simpleDispatcher(function(\FastRoute\RouteCollector $r) {
            foreach(Application::getInstance()->routes as $route) {
                list($method, $uri, $callable) = $route;
                $r->addRoute($method, $uri, $callable);
            }
        });
        // FastRoute匹配当前路由
        $routeInfo = $dispatcher->dispatch($httpMethod, $uri);
        $next = function($request) use ($routeInfo, $httpMethod, $uri) {
            if ($routeInfo[0] == \FastRoute\Dispatcher::FOUND) {                    
                if (count($routeInfo[2])) { // 支持 path 参数, 规则参考FastRoute
                    $request->attributes->add($routeInfo[2]);
                }
                if (is_array($routeInfo[1])) {
                    list($classPath, $actionName) = $routeInfo[1];
                    $controller = Application::getInstance()->get(ControllerBuilder::class)->build($classPath);
                    $routeInstance = $controller->getRoute($actionName);
                    $routeInstance->hooks = array_merge($controller->hooks, $routeInstance->hooks); // 合并class + method hook
                    return $routeInstance->invoke($request, $classPath, $actionName);
                } elseif ($routeInfo[1] instanceof \Closure) { // 手动注册的闭包路由
                    return $routeInfo[1]($request);
                } else {
                    throw new BadCodeException("无法解析路由");
                }
            } elseif ($routeInfo[0] == \FastRoute\Dispatcher::NOT_FOUND) {
                throw new BadRequestException("{$uri} 访问地址不存在");
            } elseif ($routeInfo[0] == \FastRoute\Dispatcher::METHOD_NOT_ALLOWED) {
                throw new BadRequestException("{$uri} 不支持 {$httpMethod} 请求");
            } else {
                throw new BadRequestException("unknown dispatch return {$routeInfo[0]}");
            }
        };

        foreach (array_reverse(Application::getInstance()->globalHooks) as $hookName){
            $next = function($request)use($hookName, $next){
                return Application::getInstance()->get($hookName)->handle($request, $next);
            };
        }

        try {
            $response = $next($request);
            $response->send();
        } catch (\Throwable $e) {
            $exceptionHandler = Application::getInstance()->get(ExceptionHandlerInterface::class);
            $exceptionHandler->render($e)->send();
        }
    }

    /**
     * 获取单列
     */
    public static function getInstance() 
    {
        return self::$_instance;
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
     * @param string $globalHooks
     */
    public function addGlobalHook($globalHook)
    {
        $this->globalHooks[] = $globalHook;
    }

    /** 
     * @param string $method
     * @param string $uri
     * @param callable $handler function(Request $request):Response
     */
    public function addRoute($method, $uri, callable $handler)
    {
        $this->routes[] = [$method, $uri, $handler];
    }

    public function getControllers() 
    {
        return $this->controllers;
    }

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
     * 所有controller类名
     * @var string 
     * */
    private $controllers = [];

    /**
     * 全局Hook
     * 
     * @var string[] Hook类全命名空间
     */
    private $globalHooks = [];

    /**
     * 单列
     */
    private static $_instance;
}