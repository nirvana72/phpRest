<?php
namespace PhpRest;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\ParameterBag;
use PhpRest\Exception\IExceptionHandler;
use PhpRest\Exception\ExceptionHandler;
use PhpRest\Render\IResponseRender;
use PhpRest\Render\ResponseRender;
use Doctrine\Common\Cache\Cache;
use Doctrine\Common\Cache\ApcuCache;
use Doctrine\Common\Cache\FilesystemCache;

class Application
{
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
     * @var array 
     * */
    private $routes = [];

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
            IExceptionHandler::class => \DI\create(ExceptionHandler::class),
            // 默认输出处理器
            IResponseRender::class => \DI\create(ResponseRender::class)
        ];

        // 缓存对象
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
            $exceptionHandler = $this->get(IExceptionHandler::class);
            $exceptionHandler->render($e)->send();
            exit;
        }
    }

    /**
     * 解析请求
     */
    public function dispatch() 
    {
        $app = $this;
        // 把解析注解收集的信息，注册成FastRoute路由
        $dispatcher = \FastRoute\simpleDispatcher(function(\FastRoute\RouteCollector $r) use($app) {
            foreach($app->routes as $route) {
                list($method, $uri, $classPath, $actionName) = $route;
                $r->addRoute($method, $uri, [$classPath, $actionName]);
            }
        });

        $request = $this->get(Request::class);
        $httpMethod = $request->getMethod();
        $uri = $request->getRequestUri();
        // Strip query string (?foo=bar) and decode URI
        if (false !== $pos = strpos($uri, '?')) {
            $uri = substr($uri, 0, $pos);
        }
        $uri = rawurldecode($uri);

        // FastRoute匹配当前路由
        $routeInfo = $dispatcher->dispatch($httpMethod, $uri);
        try {
            if ($routeInfo[0] == \FastRoute\Dispatcher::FOUND) {
                if (count($routeInfo[2])) { // 支持 path 参数, 规则参考FastRoute
                    $request->attributes->add($routeInfo[2]);
                }
                list($classPath, $actionName) = $routeInfo[1];

                $cache = $app->get(Cache::class);
                $cacheKey = 'controllerBuilder::build' . md5($classPath);
                $controller = $cache->fetch($cacheKey);
                if ($controller === false) {
                    $controller = $app->controllerBuilder->build($classPath);
                    $cache->save($cacheKey, $controller);
                }
                
                $routeInstance = $controller->getRoute($actionName);
                $routeInstance->hooks = array_merge($controller->hooks, $routeInstance->hooks); // 合并class + method hook
                $response = $routeInstance->invoke($app, $request, $classPath, $actionName);
                $response->send();

            } elseif ($routeInfo[0] == \FastRoute\Dispatcher::NOT_FOUND) {
                \PhpRest\abort("{$uri} 访问地址不存在");
            } elseif ($routeInfo[0] == \FastRoute\Dispatcher::METHOD_NOT_ALLOWED) {
                \PhpRest\abort("{$uri} 不支持 {$httpMethod} 请求");
            } else {
                \PhpRest\abort("unknown dispatch return {$routeInfo[0]}");
            }
        } catch (\Throwable $e) {
            $exceptionHandler = $app->get(IExceptionHandler::class);
            $exceptionHandler->render($e)->send();
        }
    }

    /**
     * @param string $id
     * @return object
     */
    public function get($id) 
    {
        return $this->container->get($id);
    }

    /**
     * @return DI\Container
     */
    public function getDIContainer() 
    {
        return $this->container;
    }

    public static function createRequestFromSymfony()
    {
        $request = Request::createFromGlobals();
        if (0 === strpos($request->headers->get('CONTENT_TYPE'), 'application/json')
            && in_array(strtoupper($request->server->get('REQUEST_METHOD', 'GET')), array('POST', 'PUT', 'DELETE', 'PATCH'))
        ) {
            $data = $request->toArray(); // method was introduced in Symfony 5.2.
            $request->request = new ParameterBag($data);
        }
        return $request;
    }
}