<?php
namespace PhpRest\Hook;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class CorsHook implements HookInterface
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
        $default = [
            'Access-Control-Allow-Origin' => '*',
            'Access-Control-Allow-Headers' => '*',
            'Access-Control-Allow-Methods' => '*',
            'Access-Control-Allow-Credentials' => 'true'
        ];

        $crosConfig = [];
        if ($this->app->has('crosHeaders')) {
            $crosConfig = $this->app->get('crosHeaders');
        }
        $headers = array_merge($default, $crosConfig);

        if ($request->getMethod() == 'OPTIONS') {
            $response = new Response('', 200); 
        } else {
            $response = $next($request);
        }

        foreach ($headers as $k => $v) {
            $response->headers->set($k, $v);
        }

        return $response;
    }
}