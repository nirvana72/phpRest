<?php
namespace PhpRest\Controller\Annotation;

use PhpRest\Controller\Controller;

class ReturnHandler
{
    /**
     * @param Controller $container
     * @param AnnotationBlock|AnnotationTag $ann
     */
    public function __invoke(Controller $controller, $ann) 
    {
        echo "<br><br> ReturnHandler";
    }
}