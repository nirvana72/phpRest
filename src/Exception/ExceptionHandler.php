<?php
namespace PhpRest\Exception;

use PhpRest\Application;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;

class ExceptionHandler implements ExceptionHandlerInterface
{
    /**
     * @param \Throwable $e
     * @return Response
     */
    public function render(\Throwable $e)
    {
        $response = Application::getInstance()->make(Response::class);
        $response->setContent($e->getMessage());
        if($e instanceof HttpException){
            $response->setStatusCode($e->getStatusCode());
        } if($e instanceof \InvalidArgumentException){
            $response->setStatusCode(Response::HTTP_BAD_REQUEST);
        }else{
            $response->setStatusCode(Response::HTTP_INTERNAL_SERVER_ERROR);
        }
        return $response;
    }
}