<?php
namespace PhpRest;

if (! function_exists('PhpRest\abort')) {
    /**
     * 抛出异常, 并记录日志
     * @param string|\Exception $error
     * @throws \Throwable
     */
    function abort($error = '') {
        if($error instanceof \Throwable){
            $e = $error;
        }else{
            $e = new \RuntimeException($error);
        }
        throw $e;
    }
}