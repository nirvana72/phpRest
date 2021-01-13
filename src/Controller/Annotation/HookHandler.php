<?php
namespace PhpRest\Controller\Annotation;

use PhpRest\Controller\Controller;

class HookHandler
{
    /**
     * @param Controller $controller
     * @param AnnotationBlock|AnnotationTag $ann
     */
    public function __invoke(Controller $controller, $ann) 
    {
        echo "<br><br> HookHandler";
    }
}