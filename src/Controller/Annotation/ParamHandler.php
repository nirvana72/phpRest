<?php
namespace PhpRest\Controller\Annotation;

use PhpRest\Controller\Controller;

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
        $paramMeta or \PhpRest\abort("{$controller->classPath}::{$target} 注解参数 {$name} 没有被使用");
        $paramMeta->description = $doc;
        
        // 支持更多的注解验证类型
        if(in_array($type, ['int', 'integer', 'numeric', 'email', 'url', 'alpha', 'alphaNum', 'slug', 'date', 'time', 'dateTime'])) {
            if ($type === 'int') $type = 'integer';
            if ($type === 'time') $type = 'dateFormat=H:i:s';
            if ($type === 'dateTime') $type = 'dateFormat=Y-m-d H:i:s';
            $paramMeta->validation = $type;
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