<?php
namespace PhpRest\Render;

use Symfony\Component\HttpFoundation\Response;

class ResponseRender implements ResponseRenderInterface
{
    /**
     * @Inject
     * @var \PhpRest\Application
     */
    private $app;

    /**
     * @param $return
     */
    public function render($return)
    {
        //直接返回Response时, 对return不再做映射
        if($return instanceof Response){ 
            return $return;
        }

        $response = $this->app->make(Response::class);
        
        if ($return !== null) {
            $value = json_encode($return, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
            $response->setContent($value);
            $response->headers->set('Content-Type', 'application/json');
        }

        return $response;
    }
}