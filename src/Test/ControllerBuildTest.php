<?php
namespace PhpRest\Test;

class ControllerBuildTest
{
    /**
     * @Inject
     * @var \PhpRest\Controller\ControllerBuilder
     */
    private $controllerBuilder;

    public function test1() {
        $builder = new \PhpRest\Controller\ControllerBuilder();
        $controller = $this->controllerBuilder->build('App\Controller\Index2Controller');
        \PhpRest\dump($controller);
    }
}