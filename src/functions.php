<?php
namespace PhpRest;

if (! function_exists('PhpRest\abort')) {
    /**
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

if (! function_exists( 'PhpRest\dump' )) {
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

if (! function_exists( 'PhpRest\uncamelize' )) {
    /**
     * 驼峰转下划线
     * @return string
     */
    function uncamelize($str, $separator = '_'): string
    {
        return strtolower(preg_replace('/([a-z])([A-Z])/', "$1" . $separator . "$2", $str));
    }
}

if (! function_exists( 'PhpRest\camelize' )) {
    /**
     * 下划线转驼峰
     * @return string
     */
    function camelize($str, $separator = '_'): string
    {
        $str = $separator. str_replace($separator, ' ', strtolower($str));
        return ltrim(str_replace(' ', '', ucwords($str)), $separator );
    }
}

if (! function_exists( 'PhpRest\isAssocArray' )) {
    /**
     * 判断是否为关联数组
     * @param array $ary
     * @return bool
     */
    function isAssocArray(array $ary): bool
    {
        if (is_array($ary) === false) return false;
        return array_keys($ary) !== range(0, count($ary) - 1);
    }
}

if (! function_exists( 'PhpRest\camelizeArrayKey' )) {
    /**
     * 将关联数组转换成驼峰KEY
     * @param array $ary
     * @return array
     */
    function camelizeArrayKey(array $ary): array
    {
        if (is_array($ary) === false) return $ary;
        if (count($ary) === 0) return $ary;
        $isAssocArray = \PhpRest\isAssocArray($ary);
        if ($isAssocArray) { $ary = [ $ary ]; }
        $tmpAry = [];
        foreach($ary as $item) {
            $tmp = [];
            foreach($item as $k => $v) {
                $k = \PhpRest\camelize($k);
                $tmp[$k] = $v;
            }
            array_push($tmpAry, $tmp);
        }
        return $isAssocArray? $tmpAry[0] : $tmpAry;
    }
}

if (! function_exists( 'PhpRest\AssocArraySearch' )) {
  /**
   * 将关联数组列表中条件查询
   * @param array $rows
   * @param array $filter
   * @param bool $findAll 是否查询所有
   * @return array
   */
  function AssocArraySearch(array $rows, array $filter, bool $findAll = false): ?array
  {
      $result = [];
      foreach($rows as $row) {
          $march = true;
          foreach($filter as $k => $v) {
              if ($row[$k] !== $v) {
                  $march = false;
                  break;
              }
          }
          if ($march === true) {
              if ($findAll === false) {
                  return $row;
              } else {
                  $result[] = $row;
              }
          }
      }
      if ($findAll === false) return null;
      return $result;
  }
}