<?php
namespace PhpRest\Controller;

use Symfony\Component\HttpFoundation\Request;
use PhpRest\Render\ResponseRenderInterface;
use PhpRest\Application;
use PhpRest\Meta\HookMeta;

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
     * @var bool
     */
    public $swagger = true;

    /**
     * 返回描述 [类型, json]
     * @var string[]
     */
    public $return = ['', ''];

    /**
     * @var HookMeta[]
     */
    public $hooks = [];

    /**
     * @var RequestHandler
     */
    public $requestHandler;

    /**
     * @param Request $request
     * @param string $classPath
     * @param string $actionName
     */
    public function invoke($request, $classPath, $actionName) 
    {
        $next = function($request) use ($classPath, $actionName) {
            $params = $this->requestHandler->makeParams($request);
            $ctlClass = Application::getInstance()->get($classPath);
            $res = call_user_func_array([$ctlClass, $actionName], $params);
            $responseRender = Application::getInstance()->get(ResponseRenderInterface::class);
            return $responseRender->render($res);
        };

        foreach (array_reverse($this->hooks) as $hookMeta) {
            $next = function($request)use($hookMeta, $next) {
                $params['method'] = $this->method;
                $params['uri']    = $this->uri;
                $params['params'] = $hookMeta->params;
                $hook = Application::getInstance()->make($hookMeta->classPath, $params);
                return $hook->handle($request, $next);
            };
        }

        return $next($request);
    }
}