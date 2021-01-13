<?php
namespace PhpRest\Controller\Annotation;

use PhpRest\Controller\Controller;
use PhpRest\Controller\Hook\HookInterface;
use PhpRest\Annotation\AnnotationTag;
use PhpRest\Meta\HookMeta;

class HookHandler
{
    /**
     * @param Controller $controller
     * @param AnnotationTag $ann
     */
    public function __invoke(Controller $controller, AnnotationTag $ann) 
    {
        $target = $ann->parent->name;
        $route = $controller->getRoute($target);
        if(!$route) { return; }

        $array = explode(' ', $ann->description);
        $hook = new HookMeta();
        $hook->classPath = $array[0];
        $hook->params    = $array[1];

        is_subclass_of($hook->classPath, HookInterface::class) or \PhpRest\abort("{$hook->classPath} 必须继承于 HookInterface");
        
        $route->hooks[] = $hook;
    }
}