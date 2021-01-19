<?php
namespace PhpRest\Controller\Annotation;

use PhpRest\Annotation\AnnotationTag;
use PhpRest\Controller\Controller;
use PhpRest\Controller\Route;
use PhpRest\Controller\RequestHandler;
use PhpRest\Controller\ResponseHandler;
use PhpRest\Meta\ParamMeta;
use PhpRest\Exception\BadCodeException;

class RouteHandler
{
    /**
     * @param Controller $container
     * @param AnnotationTag $ann
     */
    public function __invoke(Controller $controller, AnnotationTag $ann) 
    {
        $array = explode(' ', trim(preg_replace ( "/\s(?=\s)/","\\1", $ann->description)));
        count($array) === 2 or \PhpRest\abort(new BadCodeException("{$controller->classPath}->{$ann->parent->summary} @route 注解格式不正确"));

        $methodType = strtoupper($array[0]);
        $methodUri  = $array[1]; // 支持 path 参数, 规则参考FastRoute
        $actionName = $ann->parent->name; // 方法名
        in_array($methodType, ['GET','POST','PUT','HEAD','PATCH','OPTIONS','DELETE']) or \PhpRest\abort(new BadCodeException("{$controller->classPath}::{$ann->parent->summary} @route 注解方法不支持"));
        
        // 反射类文件对象
        $classRef = new \ReflectionClass($controller->classPath);
        $method = $classRef->getMethod($actionName);

        // 实例化一个路由对象
        $route = new Route();
        $route->method      = $methodType;
        $route->uri         = $controller->path . $methodUri;
        $route->summary     = $ann->parent->summary?:$actionName;
        $route->description = $ann->parent->description;
        $route->requestHandler  = new RequestHandler();

        // 收集 path 参数
        $routeParser = new \FastRoute\RouteParser\Std();
        $pathInfo = $routeParser->parse($route->uri);
        if(isset($pathInfo[0])){
            foreach ($pathInfo[0] as $i){
                if(is_array($i)) {
                    $meta = new ParamMeta();
                    $meta->name        = $i[0];
                    $meta->source      = "attributes.{$meta->name}";
                    $meta->isOptional  = false;
                    $meta->default     = null;
                    $meta->description = $meta->name; // 默认参数
                    $route->requestHandler->addParamMeta($meta);
                }
            }
        }
        
        $paramSource = in_array($methodType, ['POST', 'PUT']) ? 'request' : 'query';
        // 遍历方法的参数，封装成 ParamMeta 对象, 收集到route->requestHandler里
        $methodParams = $method->getParameters();
        foreach ($methodParams as $param) {
            $paramName = $param->getName(); // 参数名 不带$

            $meta = new ParamMeta();
            $meta->name        = $paramName;
            $meta->source      = "{$paramSource}.{$paramName}";
            $meta->isOptional  = $param->isOptional();
            $meta->default     = $param->isOptional()?$param->getDefaultValue():null;
            $meta->description = $paramName; // 默认参数描述为参数名，也就是说@param可以不写, 如果写了则在@param解析时覆盖

            // 参数是不是指定了一个实体类
            $paramClass = $param->getClass(); // 参数类型（对象），不写或写基础数据类型为NULL
            if($paramClass){ // 如果参数是个Class,否则(基础数据类型)这里是null
                $meta->type = ['Entity', $paramClass->getName()];
                if ($meta->type[1] === 'Symfony\Component\HttpFoundation\Request') {
                    $meta->type = ['Request', ''];
                }
            }

            $route->requestHandler->addParamMeta($meta);
        }
        // 添加路由
        $controller->addRoute($actionName, $route);
    }
}