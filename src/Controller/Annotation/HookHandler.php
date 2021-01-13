<?php
namespace PhpRest\Controller\Annotation;

use PhpRest\Controller\Controller;
use PhpRest\Controller\Hook\HookInterface;
use PhpRest\Annotation\AnnotationTag;
use PhpRest\Meta\HookMeta;

class HookHandler
{
    /**
     * 可以同时在 class 和 method 上指定 hook
     * 执行顺序 先 class->hook 再 method->hook
     * 如果同时定义同名 hook , 以 method上的为准
     * 
     * @param Controller $controller
     * @param AnnotationTag $ann
     */
    public function __invoke(Controller $controller, AnnotationTag $ann) 
    {
        $array = explode(' ', $ann->description);
        $hook = new HookMeta();
        $hook->classPath = $array[0];
        $hook->params    = $array[1];

        is_subclass_of($hook->classPath, HookInterface::class) or \PhpRest\abort("{$hook->classPath} 必须继承于 HookInterface");

        if ($ann->parent->position === 'class') {
            $controller->hooks[$hook->classPath] = $hook;
        }

        if ($ann->parent->position === 'method') {
            $target = $ann->parent->name;
            $route = $controller->getRoute($target);
            if(!$route) { return; }            
            $route->hooks[$hook->classPath] = $hook;
        }
    }
}