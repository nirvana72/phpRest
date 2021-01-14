<?php
namespace PhpRest\Controller;

use PhpRest\Meta\HookMeta;

class Controller
{
    /**
     * @var string
     */
    public $summary = '';

    /**
     * @var string
     */
    public $description = '';

    /**
     * path /xxx
     * @var string
     */
    public $path = '';

    /**
     * 类命名空间(调用时实例化用)
     * @var string
     */
    public $classPath;

    /**
     * 文件物理路径(验证缓存过期用)
     * @var string
     */
    public $filePath;

    /**
     * 上次修改时间(验证缓存过期用)
     * @var string
     */
    public $modifyTimespan;
    
    /**
     * controller下的路由集合
     * @var Route[]
     */
    public $routes = [];

    /**
     * @var HookMeta[]
     */
    public $hooks = [];
    
    /**
     * @param string $classPath controller类的命名空间
     */
    public function __construct($classPath) 
    {
        $this->classPath = $classPath;
        // 默认路由 OrderTypeController = /order_type
        $shortName = end(explode('\\', $classPath));
        $shortName = substr($shortName,0 , -10);
        $shortName = \PhpRest\uncamelize($shortName);
        $this->path = '/' . $shortName;
    }

    /**
     * 添加路由
     * 
     * @param Route $route
     * @param string $actionName class method
     * @return void
     */
    public function addRoute($actionName, Route $route) 
    {
        !array_key_exists($actionName, $this->routes) or \PhpRest\abort("路由重复 {$this->classPath} {$actionName}");
        $this->routes[$actionName] = $route;
    }

    /**
     * 获取指定名称的路由
     * 
     * @param $actionName
     * @return Route|false
     */
    public function getRoute($actionName) 
    {
        if (array_key_exists($actionName, $this->routes)){
            return $this->routes[$actionName];
        }
        return false;
    }
}