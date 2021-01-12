<?php
namespace PhpRest\Render;

use Symfony\Component\HttpFoundation\Response;

class ResponseRender implements IResponseRender
{
    /**
     * @param $return
     */
    public function render($return)
    {
        //直接返回Response时, 对return不再做映射
        if($return instanceof Response){ 
            return $return;
        }

        $response = new Response();
        
        if ($return !== null) {
            $response->headers->set('Content-Type', 'application/json');
            $value = json_encode($return, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
            $response->setContent($value);
        }

        return $response;
    }
}