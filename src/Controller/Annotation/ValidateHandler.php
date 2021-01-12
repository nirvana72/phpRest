<?php
namespace PhpRest\Controller\Annotation;

use PhpRest\Controller\Controller;
// use PhpRest\Validator\Validator;

class ValidateHandler
{
    /**
     * @param Controller $controller
     * @param AnnotationBlock|AnnotationTag $ann
     */
    public function __invoke(Controller $controller, $ann) 
    {
        if ($ann->description === '') return;

        $target = $ann->parent->parent->name;
        $route = $controller->getRoute($target);
        if(!$route) { return; }

        list($type, $name, $doc) = ParamHandler::resolveParam($ann->parent->description);
        $paramMeta = $route->requestHandler->getParamMeta($name); // 验证器对应的参数
        $paramMeta->validation = $ann->description;
    }
}