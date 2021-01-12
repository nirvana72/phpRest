<?php
namespace PhpRest;

use Symfony\Component\HttpFoundation\Request;

class Application
{
    /**
     * @Inject
     * @var \PhpRest\Controller\ControllerBuilder
     */
    private $controllerBuilder;

    /** @var \DI\Container */
    private $container;

    /** @var array */
    private $routes = [];

    /**
     * 创建app对象
     * 
     * @param string|array $conf
     * @return Application
     */
    public static function createDefault($conf = []) 
    {
        $builder = new \DI\ContainerBuilder();
        $builder->addDefinitions($conf);
        $builder->useAutowiring(false);
        $builder->useAnnotations(true);
        $container = $builder->build();

        $app = $container->make(self::class);
        $app->container = $container;
        return $app;
    }

    /**
     * PHP-DI 获取注入对象
     * 
     * @param string $id
     * @return object
     */
    public function get($id) 
    {
        return $this->container->get($id);
    }

    /**
     * 加载controller
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
     * 加载controller
     * 
     * @param string $classPath controller命名空间全路径
     */
    private function loadRoutesFromClass($classPath) 
    {
        $controller = $this->controllerBuilder->build($classPath);
        foreach ($controller->routes as $actionName => $route) {
            $this->routes[] = [$route->method, $route->uri, [$classPath, $actionName]];
        }
    }

    /**
     * 解析请求
     */
    public function dispatch() 
    {
        $app = $this;
        $dispatcher = \FastRoute\simpleDispatcher(function(\FastRoute\RouteCollector $r) use($app) {
            foreach($app->routes as $route) {
                list($method, $uri, $callable) = $route;
                $r->addRoute($method, $uri, $callable);
            }
        });

        $request = Request::createFromGlobals();
        $httpMethod = $request->getMethod();
        $uri = $request->getRequestUri();
        // Strip query string (?foo=bar) and decode URI
        if (false !== $pos = strpos($uri, '?')) {
            $uri = substr($uri, 0, $pos);
        }
        $uri = rawurldecode($uri);

        $routeInfo = $dispatcher->dispatch($httpMethod, $uri);
        if ($routeInfo[0] == \FastRoute\Dispatcher::FOUND) {
            list($classPath, $actionName) = $routeInfo[1];
            $controller = $app->controllerBuilder->build($classPath);
            $routeInstance = $controller->getRoute($actionName);
            $routeInstance->invoke($app, $request, $classPath, $actionName);
        }
    }
}