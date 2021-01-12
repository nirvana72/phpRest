<?php
namespace PhpRest\Controller;

use Symfony\Component\HttpFoundation\Request;
use PhpRest\Exception\IExceptionHandler;
use PhpRest\Render\IResponseRender;

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
        try {
            $params = $this->requestHandler->makeParams($app, $request);
            $ctlClass = $app->get($classPath);
            $res = call_user_func_array([$ctlClass, $actionName], $params);
            $responseRender = $app->get(IResponseRender::class);
            return $responseRender->render($res);
        } catch (\Throwable $e) {
            $exceptionHandler = $app->get(IExceptionHandler::class);
            return $exceptionHandler->render($e);
        }
    }
}