<?php
namespace PhpRest\Controller\Annotation;

use PhpRest\Controller\Controller;

class PathHandler
{
    /**
     * @param Controller $container
     * @param AnnotationBlock|AnnotationTag $ann
     */
    public function __invoke(Controller $controller, $ann) 
    {
        // controller 注解上的 path, 可以为空
        $controller->prefix = $ann->description;
    }
}