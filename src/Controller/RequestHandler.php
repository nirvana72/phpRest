<?php
namespace PhpRest\Controller;

use Symfony\Component\HttpFoundation\Request;
use PhpRest\Meta\ParamMeta;
use PhpRest\Utils\ArrayAdaptor;
use PhpRest\Validator\Validator;

class RequestHandler
{
    /**
     * @var ParamMeta[]
     */
    public $paramMetas = [];

    /**
     * 获取指定参数信息
     * @param $name
     * @return ParamMeta|null
     */
    public function getParamMeta($name)
    {
        foreach ($this->paramMetas as $meta){
            if($meta->name == $name){
                return $meta;
            }
        }
        return null;
    }

    /**
     * 从request中获取需要的参数
     * 
     * @param Application $app
     * @param Request $request
     * @return array
     */
    public function makeParams($app, $request) 
    {
        $req = ['request' => $request];
        $requestArray = new ArrayAdaptor($req);
        $inputs = [];
        $vld = new Validator();

        // 从 request 中收集所需参数
        foreach ($this->paramMetas as $meta) {
            $source = \JmesPath\search("request.{$meta->name}", $requestArray);
            if ($source !== null) {
                $inputs[$meta->name] = $source;

                // 验证参数规则
                if($meta->validation) {
                    $vld->rule($meta->validation, $meta->name);
                }
            } else {
                $meta->isOptional or \PhpRest\abort("缺少参数 '{$meta->name}'");
                $inputs[$meta->name] = $meta->default;
            }
        }

        // 验证参数
        $vld = $vld->withData($inputs);
        $vld->validate() or \PhpRest\abort(json_encode($vld->errors()));

        $params = [];
        foreach($inputs as $_ => $val) {
            $params[] = $val;
        }
        return $params;
    }
}