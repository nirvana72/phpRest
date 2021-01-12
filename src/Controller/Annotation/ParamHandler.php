<?php
namespace PhpRest\Controller\Annotation;

use PhpRest\Controller\Controller;
use PhpRest\Utils\TypeHint;

class ParamHandler
{
    /**
     * @param Controller $container
     * @param AnnotationBlock|AnnotationTag $ann
     */
    public function __invoke(Controller $controller, $ann) 
    {
        $target = $ann->parent->name;
        $route = $controller->getRoute($target);
        if(!$route) { return; }
        list($type, $name, $doc) = self::resolveParam($ann->description);

        $paramMeta = $route->requestHandler->getParamMeta($name);
        $paramMeta or \PhpBoot\abort("{$controller->classPath}->{$target} 参数 {$paramName} 不存在");
        $paramMeta->description = $doc;
        if($type) {
            $paramMeta->type = TypeHint::normalize($type, $controller->classPath);
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