<?php
namespace PhpRest\Controller\Annotation;

use PhpRest\Controller\Controller;
use PhpRest\Annotation\AnnotationTag;

class ReturnHandler
{
    /**
     * @param Controller $container
     * @param AnnotationTag $ann
     */
    public function __invoke(Controller $controller, AnnotationTag $ann) 
    {
        $method = $ann->parent->name;
        $route = $controller->getRoute($method);
        if ($route === false) { return; }

        $route->return = $ann->description;
    }
}