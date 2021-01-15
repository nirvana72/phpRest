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
        list($type, $name, $desc) = $ann->description;

        $paramMeta = $route->requestHandler->getParamMeta($name);
        $paramMeta or \PhpRest\abort("{$controller->classPath}::{$target} 注解参数 {$name} 没有被使用");
        $paramMeta->description = $desc;
        
        if ($paramMeta->type[0] === 'entity') {
            if (empty($type) === false) {
                // 绑定实体类参数，@param 可以不写类型，默认按参数描述指定
                // 但是 @param 如果写了类型，就需要验证类型一至
                $paramTypeInMethodName = strpos($type, '\\') !== false ? $paramMeta->type[1] : end(explode('\\', $paramMeta->type[1]));
                $type === $paramTypeInMethodName or \PhpRest\abort("{$controller->classPath}::{$target} 实体类参数 {$name} 与@param描述不一至");
            }
        }
        else {
            // function 中没有指定参数类型，判断 @param 中指定是否为实体类
            // 带 \ 命名空间 或首字母大写， 认为是实体类
            if (strpos($type, '\\') !== false || preg_match("/^[A-Z]{1}$/", $type[0])) {
                if (strpos($type, '\\') === false) {
                    // 如果没写全命名空间，需要通过反射取得全命名空间
                    $type = \PhpRest\Utils\ReflectionHelper::resolveFromReflector($controller->classPath, $type);
                }
                class_exists($type) or \PhpRest\abort("{$controller->classPath}::{$target} @param {$name} 指定的实体类 {$type} 不存在");
                $paramMeta->type = ['entity', $type];
            } else {
                // 否则作为基础类型处理
                $cast = \PhpRest\Validator\Validator::typeCast($type);
                list($realType, $validation, $desc) = $cast;
                $paramMeta->validation = $validation;
                $paramMeta->type = [$realType, $desc];
            }
        }
    }
}