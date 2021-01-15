<?php
namespace PhpRest\Controller;

use Symfony\Component\HttpFoundation\Request;
use PhpRest\Meta\ParamMeta;
use PhpRest\Utils\ArrayAdaptor;
use PhpRest\Validator\Validator;
use PhpRest\Entity\EntityBuilder;

class RequestHandler
{
    /**
     * @var ParamMeta[]
     */
    public $paramMetas = [];

    /**
     * 添加参数
     * 
     * @param ParamMeta $meta meta
     */
    public function addParamMeta($meta) 
    {
        if(!array_key_exists($meta->name, $this->paramMetas)) {
            $this->paramMetas[$meta->name] = $meta;
        }
    }

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
        \Valitron\Validator::lang('zh-cn');
        // $req = ['request' => $request];
        $requestArray = new ArrayAdaptor($request);
        $inputs = [];
        // 从 request 中收集所需参数
        foreach ($this->paramMetas as $_ => $meta) {
            // 直接绑定Request
            if ($meta->type[0] === 'request') {
                $inputs[$meta->name] = $request;
                continue;
            }
            
            $source = \JmesPath\search($meta->source, $requestArray);
            if ($source === null) {
                $meta->isOptional or \PhpRest\abort("缺少参数 '{$meta->name}'");
                $inputs[$meta->name] = $meta->default;
            } else {
                $source = ArrayAdaptor::strip($source); // 还原适配器封装
                
                if ($meta->type[0] === 'entity') {
                    // 实体参数
                    $entityClassPath = $meta->type[1];
                    $entityBuilder = $app->get(EntityBuilder::class);
                    $entity = $entityBuilder->build($entityClassPath);
                    $inputs[$meta->name] = $entity->makeInstanceWithData($app, $source);
                } else {
                    // 基础类型，验证规则
                    if($meta->validation) {
                        $vld = new Validator([$meta->name => $source]);
                        $vld->rule($meta->validation, $meta->name);
                        if (false === $vld->validate()) {
                            $error = $vld->errors();
                            \PhpRest\abort($error[$meta->name][0]);
                        }
                    }
                    $inputs[$meta->name] = $source;
                }

                // TODO 数组支持
            }
        }

        $params = [];
        foreach($inputs as $_ => $val) {
            $params[] = $val;
        }
        
        return $params;
    }
}