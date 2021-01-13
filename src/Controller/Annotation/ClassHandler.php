<?php
namespace PhpRest\Controller\Annotation;

use PhpRest\Controller\Controller;
use PhpRest\Annotation\AnnotationBlock;

class ClassHandler
{
    /**
     * @param Controller $container
     * @param AnnotationBlock $ann
     */
    public function __invoke(Controller $controller, AnnotationBlock $ann) 
    {
        $controller->summary     = $ann->summary;
        $controller->description = $ann->description;
    }
}