<?php
namespace PhpRest\Controller\Annotation;

use PhpRest\Controller\Controller;
use PhpRest\Annotation\AnnotationTag;

class BindHandler
{
    /**
     * @param Controller $controller
     * @param AnnotationTag $ann
     */
    public function __invoke(Controller $controller, AnnotationTag $ann) 
    {
        $target = $ann->parent->parent->name;
        $route = $controller->getRoute($target);
        if ($route === false) { return; }

        list($type, $name, $desc) = $ann->parent->description;
        $paramMeta = $route->requestHandler->getParamMeta($name);
        $paramMeta->source = $ann->description;
    }
}