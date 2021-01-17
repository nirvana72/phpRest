<?php
namespace PhpRest;

class ApiResult 
{
    /**
     * code
     * @var int
     */
    public $code = 1;

    /**
     * msg
     * @var string
     */
    public $msg = 'success';

    public $data;

    public function __construct($code, $msg) {
        $this->code = $code;
        $this->msg = $msg;
    }

    public static function success($data = null) {
        $result = new ApiResult(1, 'success');
        $result->data = $data;
        return $result;
    }

    public static function error($msg, $code = -1) {
        return new ApiResult($code, $msg);
    }

    public static function assert($flag, $msg = []) {
        $errMsg = '出错了';
        $sucMsg = 'success';
        if (is_string($msg)) {
            $errMsg = $msg;
        }
        if (is_array($msg)) {
            $cnt = count($msg);
            if ($cnt === 1) { $errMsg = $msgs[0]; }
            if ($cnt === 2) { $sucMsg = $msgs[0]; $errMsg = $msgs[1]; }
        }
        $code = $flag ? 1 : -1;
        $msg = $flag ? $successMsg : $errMsg;
        return new ApiResult($code, $msg);
    }
}