<?php
namespace PhpRest;

class ApiResult 
{
    /**
     * ret
     * @var int
     */
    public $ret = 1;

    /**
     * msg
     * @var string
     */
    public $msg = 'success';

    public $data;

    public function __construct($ret, $msg) {
        $this->ret = $ret;
        $this->msg = $msg;
    }

    public static function success($data = null): ApiResult
    {
        $result = new ApiResult(1, 'success');
        $result->data = $data;
        return $result;
    }

    public static function error($msg, $ret = -1): ApiResult
    {
        return new ApiResult($ret, $msg);
    }

    public static function assert($flag, $msg = []): ApiResult
    {
        $sucMsg = $msg[0]?:'success';
        $errMsg = $msg[1]?:'出错了';
        $ret = $flag ? 1 : -1;
        $msg = $flag ? $sucMsg : $errMsg;
        return new ApiResult($ret, $msg);
    }
}