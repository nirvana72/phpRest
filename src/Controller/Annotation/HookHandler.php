<?php
namespace PhpRest\Controller\Annotation;

use PhpRest\Controller\Controller;
use PhpRest\Hook\HookInterface;
use PhpRest\Annotation\AnnotationTag;
use PhpRest\Meta\HookMeta;
use PhpRest\Exception\BadCodeException;

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
        // 去除多余空格后切分成数组 1路径 2参数
        $array = explode(' ', trim(preg_replace ( "/\s(?=\s)/","\\1", $ann->description)));

        $classPath = $array[0];
        // 如果第一位不是 \ 则认为hook位于controller同层命名空间， 否则为绝对路径命名空间
        // 目前只支持绝对路径命名空间 和 controller同层命名空间
        if ($classPath[0] !== '\\') {
            // 根据controller命名空间生成hook命名空间
            $ctlName = end(explode('\\', $controller->classPath));
            $classPath = str_replace($ctlName, $classPath, $controller->classPath);
        }

        $hook = new HookMeta();
        $hook->classPath = $classPath;
        $hook->params    = $array[1];

        is_subclass_of($hook->classPath, HookInterface::class) or \PhpRest\abort(new BadCodeException("{$hook->classPath} 必须继承于 HookInterface"));

        if ($ann->parent->position === 'class') {
            $controller->hooks[$hook->classPath] = $hook;
        }

        if ($ann->parent->position === 'method') {
            $method = $ann->parent->name;
            $route = $controller->getRoute($method);
            if ($route === false) { return; }
            $route->hooks[$hook->classPath] = $hook;
        }
    }
}