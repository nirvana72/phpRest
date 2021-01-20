<?php
namespace PhpRest\Hook;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use PhpRest\Exception\ExceptionHandlerInterface;

class CrosHook implements HookInterface
{
    /**
     * @Inject
     * @var \PhpRest\Application
     */
    private $app;

    /**
     * @param Request $request
     * @param callable $next
     * @return Response
     */
    public function handle(Request $request, callable $next)
    {
        if ($request->getMethod() == 'OPTIONS') {
            $response = new Response('', 200); 
        } else {
            try {
                $response = $next($request);
            } catch(\Throwable $e){
                $exceptionHandler = $this->app->get(ExceptionHandlerInterface::class);
                $response = $exceptionHandler->render($e);
            }
        }

        $default = [
            'Access-Control-Allow-Origin'   => '*',
            'Access-Control-Allow-Headers'  => '*',
            'Access-Control-Allow-Methods'  => '*',
            'Access-Control-Allow-Credentials' => 'true'
        ];

        $crosConfig = [];
        if ($this->app->has('crosHeaders')) {
            $crosConfig = $this->app->get('crosHeaders');
        }
        $headers = array_merge($default, $crosConfig);

        foreach ($headers as $k => $v) {
            $response->headers->set($k, $v);
        }

        return $response;
    }
}