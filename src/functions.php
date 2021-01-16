<?php
namespace PhpRest;

if (!function_exists('PhpRest\abort')) {
    /**
     * 抛出异常, 并记录日志
     * @param string|\Throwable $error
     * @throws \Throwable
     */
    function abort($error = '') 
    {
        if($error instanceof \Throwable){
            $e = $error;
        }else{
            $e = new \RuntimeException($error);
        }
        throw $e;
    }
}

if (! function_exists ( 'PhpRest\dump' )) {
    /**
     * 浏览器友好的变量输出
     * @param mixed $vars 要输出的变量
     * @return void
     */
    function dump(...$vars)
    {
        ob_start();
        var_dump(...$vars);

        $output = ob_get_clean();
        $output = preg_replace('/\]\=\>\n(\s+)/m', '] => ', $output);

        if (PHP_SAPI == 'cli') {
            $output = PHP_EOL . $output . PHP_EOL;
        } else {
            if (!extension_loaded('xdebug')) {
                $output = htmlspecialchars($output, ENT_SUBSTITUTE);
            }
            $output = '<pre>' . $output . '</pre>';
        }

        echo $output;
    }
}

if (! function_exists ( 'PhpRest\uncamelize' )) {
    /**
     * 驼峰转下划线
     * @return void
     */
    function uncamelize($camelCaps, $separator = '_')
    {
        return strtolower(preg_replace('/([a-z])([A-Z])/', "$1" . $separator . "$2", $camelCaps));
    }
}

if (! function_exists ( 'PhpRest\is_assoc_array' )) {
    /**
     * 判断是否为关联数组
     * @param array $ary
     * @return bool
     */
    function is_assoc_array($ary)
    {
        if (is_array($ary) === false) return false;
        return array_keys($ary) !== range(0, count($ary) - 1);
    }
}