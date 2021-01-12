<?php
namespace PhpRest\Controller;

use Symfony\Component\HttpFoundation\Request;

class Route
{
    /**
     * httpMethod
     * @var string
     */
    public $method;

    /**
     * @var string
     */
    public $uri;

    /**
     * @var string
     */
    public $summary = '';

    /**
     * @var string
     */
    public $description = '';

    /**
     * @var RequestHandler
     */
    public $requestHandler;

    /**
     * @param Application $app
     * @param Request $request
     * @param string $classPath
     * @param string $actionName
     */
    public function invoke($app, $request, $classPath, $actionName) 
    {
        $params = $this->requestHandler->makeParams($app, $request);
        $ctlClass = $app->get($classPath);
        call_user_func_array([$ctlClass, $actionName], $params);
    }
}