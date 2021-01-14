<?php
namespace PhpRest\Controller\Annotation;

use PhpRest\Controller\Controller;
use PhpRest\Annotation\AnnotationTag;

class RuleHandler
{
    /**
     * @param Controller $controller
     * @param AnnotationTag $ann
     */
    public function __invoke(Controller $controller, AnnotationTag $ann) 
    {
        if ($ann->description === '') return;

        $target = $ann->parent->parent->name;
        $route = $controller->getRoute($target);
        if ($route === false) { return; }

        list($type, $name, $doc) = ParamHandler::resolveParam($ann->parent->description);
        $paramMeta = $route->requestHandler->getParamMeta($name); // 验证器对应的参数
        // 如果参数类型已经是个验证对象了，这里把两个验证描述合并
        $paramMeta->validation .= '|' . $ann->description;
        $paramMeta->validation = ltrim($paramMeta->validation, '|');
    }
}