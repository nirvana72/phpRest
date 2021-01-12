<?php
namespace PhpRest\Controller\Annotation;

use PhpRest\Controller\Controller;

class ClassHandler
{
    /**
     * @param Controller $container
     * @param AnnotationBlock|AnnotationTag $ann
     */
    public function __invoke(Controller $controller, $ann) 
    {
        $controller->summary     = $ann->summary;
        $controller->description = $ann->description;
    }
}