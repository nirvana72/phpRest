<?php
namespace PhpRest\Controller\Annotation;

use PhpRest\Controller\Controller;
use PhpRest\Controller\Route;
use PhpRest\Controller\RequestHandler;
use PhpRest\Controller\ResponseHandler;
use PhpRest\Meta\ParamMeta;

class RouteHandler
{
    /**
     * @param Controller $container
     * @param AnnotationBlock|AnnotationTag $ann
     */
    public function __invoke(Controller $controller, $ann) 
    {
        $array = explode(' ', $ann->description);
        count($array) === 2 or \PhpRest\abort("{$controller->classPath}->{$ann->parent->summary} @route 注解格式不正确");

        $methodType = strtoupper($array[0]);
        $actionName = $ann->parent->name;
        $methodUri  = $array[1];
        in_array($methodType, ['GET','POST','PUT','HEAD','PATCH','OPTIONS','DELETE']) or \PhpRest\abort("{$controller->classPath}::{$ann->parent->summary} @route 注解方法不支持");

        $classRef = new \ReflectionClass($controller->classPath);
        $method = $classRef->getMethod($actionName);
        $methodParams = $method->getParameters();
        // 实例化一个路由对象
        $route = new Route();
        $route->method      = $methodType;
        $route->uri         = $controller->prefix . $methodUri;
        $route->summary     = $ann->parent->summary;
        $route->description = $ann->parent->description;
        $route->requestHandler  = new RequestHandler();

        foreach ($methodParams as $param) {
            $paramName = $param->getName();
            $paramClass = $param->getClass();
            if($paramClass){ // 如果参数是个Class
                $paramClass = $paramClass->getName();
            }
            $meta = new ParamMeta();
            $meta->name        = $paramName;
            $meta->type        = $paramClass?:'mixed'; // 参数类型如不是类，这里先mixed, 在ParamAnn中根据注解内容重新定义
            $meta->isOptional  = $param->isOptional();
            $meta->default     = $param->isOptional()?$param->getDefaultValue():null;
            
            $route->requestHandler->paramMetas[] = $meta;
        }
        // 添加路由
        $controller->addRoute($actionName, $route);
    }
}