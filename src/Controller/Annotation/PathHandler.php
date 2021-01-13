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
        // controller 注解上的 path, 可以为空
        $controller->uriPrefix = $ann->description;
        if ($controller->uriPrefix === '/') {
            $controller->uriPrefix = '';
        }
    }
}