<?php
namespace PhpRest\Controller\Annotation;

use PhpRest\Controller\Controller;
use PhpRest\Annotation\AnnotationTag;

class ParamHandler
{
    /**
     * @param Controller $container
     * @param AnnotationTag $ann
     */
    public function __invoke(Controller $controller, AnnotationTag $ann) 
    {
        $target = $ann->parent->name;
        $route = $controller->getRoute($target);
        if ($route === false) { return; }
        list($type, $name, $doc) = self::resolveParam($ann->description);

        $paramMeta = $route->requestHandler->getParamMeta($name);
        $paramMeta or \PhpRest\abort("{$controller->classPath}::{$target} 注解参数 {$name} 没有被使用");
        $paramMeta->description = $doc;
        
        if ($paramMeta->type[0] !== 'entity') { // 已在@route解析时确定了实体类型
            $cast = \PhpRest\Validator\Validator::typeCast($type);
            list($realType, $validation, $desc) = $cast;
            $paramMeta->validation = $validation;
            $paramMeta->type = [$realType, $desc];
        }
    }

    public static function resolveParam($text) 
    {
        $type = null;
        $name = null;
        $doc  = '';
        $ary = explode(' ', $text);
        if($ary[0][0] === '$') { //没写类型 带$前缀的是变量
            $name = substr($ary[0], 1);
            $doc  = $ary[1];
        } else {
            $type = $ary[0];
            $name = substr($ary[1], 1);
            $doc  = $ary[2];
        }
        return [$type, $name, $doc];
    }
}