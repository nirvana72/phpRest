<?php
namespace PhpRest\Controller\Annotation;

use PhpRest\Controller\Controller;
use PhpRest\Annotation\AnnotationTag;

class PathHandler
{
    /**
     * @param Controller $container
     * @param AnnotationTag $ann
     */
    public function __invoke(Controller $controller, AnnotationTag $ann) 
    {
        // controller 注解上的 path, 可以为空默认为控制器名小写
        // 写 / 表示不设置一级路由
        if ($ann->description === '/') {
            $controller->path = '';
        } else {
            $controller->path = $ann->description;
        }
    }
}