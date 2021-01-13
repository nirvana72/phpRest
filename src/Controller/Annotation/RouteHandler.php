<?php
namespace PhpRest\Controller\Annotation;

use PhpRest\Annotation\AnnotationTag;
use PhpRest\Controller\Controller;
use PhpRest\Controller\Route;
use PhpRest\Controller\RequestHandler;
use PhpRest\Controller\ResponseHandler;
use PhpRest\Meta\ParamMeta;

class RouteHandler
{
    /**
     * @param Controller $container
     * @param AnnotationTag $ann
     */
    public function __invoke(Controller $controller, AnnotationTag $ann) 
    {
        $array = explode(' ', $ann->description);
        count($array) === 2 or \PhpRest\abort("{$controller->classPath}->{$ann->parent->summary} @route 注解格式不正确");

        $methodType = strtoupper($array[0]);
        $methodUri  = $array[1]; // 支持 path 参数, 规则参考FastRoute
        $actionName = $ann->parent->name; // 方法名
        in_array($methodType, ['GET','POST','PUT','HEAD','PATCH','OPTIONS','DELETE']) or \PhpRest\abort("{$controller->classPath}::{$ann->parent->summary} @route 注解方法不支持");
        
        // 反射类文件对象
        $classRef = new \ReflectionClass($controller->classPath);
        $method = $classRef->getMethod($actionName);

        // 实例化一个路由对象
        $route = new Route();
        $route->method      = $methodType;
        $route->uri         = $controller->uriPrefix . $methodUri;
        $route->summary     = $ann->parent->summary;
        $route->description = $ann->parent->description;
        $route->requestHandler  = new RequestHandler();
        
        // 遍历方法的参数，封装成 ParamMeta 对像
        $methodParams = $method->getParameters();
        foreach ($methodParams as $param) {
            $paramName = $param->getName();
            $paramClass = $param->getClass();
            if($paramClass){ // 如果参数是个Class,否则(基础数据类型)这里是null
                $paramClass = $paramClass->getName();
            }
            $meta = new ParamMeta();
            $meta->name        = $paramName;
            $meta->type        = $paramClass?:'mixed'; // 参数类型如不是类，这里先mixed, 在ParamAnn中根据注解内容重新定义
            $meta->isOptional  = $param->isOptional();
            $meta->default     = $param->isOptional()?$param->getDefaultValue():null;
            $meta->description = $paramName; // 默认参数描述为参数名，也就是说@param可以不写

            $route->requestHandler->paramMetas[] = $meta;
        }
        // 添加路由
        $controller->addRoute($actionName, $route);
    }
}