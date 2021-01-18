<?php
namespace PhpRest\Exception;

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
        if($e instanceof HttpException){
            return new Response($e->getMessage(), $e->getStatusCode(), $e->getHeaders());
        } if($e instanceof \InvalidArgumentException){
            return new Response($e->getMessage(), Response::HTTP_BAD_REQUEST);
        }else{
            return new Response($e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}