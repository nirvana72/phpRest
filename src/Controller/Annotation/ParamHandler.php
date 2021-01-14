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
        if(!$route) { return; }
        list($type, $name, $doc) = self::resolveParam($ann->description);

        $paramMeta = $route->requestHandler->getParamMeta($name);
        $paramMeta or \PhpRest\abort("{$controller->classPath}::{$target} 注解参数 {$name} 没有被使用");
        $paramMeta->description = $doc;
        
        if ($paramMeta->type[0] !== 'entity') { // 已在@route解析时确定了实体类型
            if(in_array($type, ['email', 'url'])) {
                $paramMeta->validation = $type;
                $paramMeta->type = ['string', $type];
            } 
            elseif(in_array($type, ['int', 'integer'])) {
                $paramMeta->validation = 'integer';
                $paramMeta->type = ['integer', 1];
            } 
            elseif($type === 'numeric') {
                $paramMeta->validation = 'numeric';
                $paramMeta->type = ['number', 1.1];
            }
            elseif($type === 'alpha') {
                $paramMeta->validation = 'alpha';
                $paramMeta->type = ['string', '只能包括英文字母(a-z)'];
            }
            elseif($type === 'alphaNum') {
                $paramMeta->validation = 'alphaNum';
                $paramMeta->type = ['string', '只能包括英文字母(a-z)和数字(0-9)'];
            }
            elseif($type === 'slug') {
                $paramMeta->validation = 'slug';
                $paramMeta->type = ['string', '只能包括英文字母(a-z)、数字(0-9)、破折号和下划线'];
            }
            elseif($type === 'date') {
                $paramMeta->validation = 'date';
                $paramMeta->type = ['string', 'yyyy-mm-dd'];
            }
            elseif($type === 'time') {
                $paramMeta->validation = 'dateFormat=H:i:s';
                $paramMeta->type = ['string', 'HH:mm:ss'];
            }
            elseif($type === 'dateTime') {
                $paramMeta->validation = 'dateFormat=Y-m-d H:i:s';
                $paramMeta->type = ['string', 'yyyy-mm-dd HH:mm:ss'];
            }
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