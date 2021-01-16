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
        $vld = new Validator([], [], 'zh-cn');
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
                $meta->isOptional or \PhpRest\abort("请求参数缺少 '{$meta->name}'");
                $inputs[$meta->name] = $meta->default;
            } else {
                $source = ArrayAdaptor::strip($source); // 还原适配器封装
                
                if ($meta->type[0] === 'Entity' || $meta->type[0] === 'Entity[]') {
                    // 参数是个实体，实体验证在Entity创建逻辑中
                    $entityClassPath = $meta->type[1];
                    $entityBuilder = $app->get(EntityBuilder::class);
                    $entity = $entityBuilder->build($entityClassPath);
                    if ($meta->type[0] === 'Entity[]') {   
                        is_array($source) or \PhpRest\abort("请求参数 '{$meta->name}' 不是数组");
                        $inputs[$meta->name] = [];
                        foreach($source as $d) {
                            $inputs[$meta->name][] = $entity->makeInstanceWithData($app, $d);
                        }                        
                    } else {
                        $inputs[$meta->name] = $entity->makeInstanceWithData($app, $source);
                    }
                } else {
                    if($meta->validation) {
                        if (substr($meta->type[0], -2) === '[]') {
                            // 验证基础数据类型数组
                            is_array($source) or \PhpRest\abort("请求参数 '{$meta->name}' 不是数组");
                            $vldAry = new Validator([$meta->name => $source], [], 'zh-cn');
                            $vldAry->rule($meta->validation, "{$meta->name}.*");
                            $vldAry->validate() or \PhpRest\abort(current($vldAry->errors())[0]);
                        }else {
                            // 验证基础数据类型
                            $vld->rule($meta->validation, $meta->name);
                        }
                    }
                    $inputs[$meta->name] = $source;
                }
            }
        }

        $vld = $vld->withData($inputs);
        $vld->validate() or \PhpRest\abort(current($vld->errors())[0]);

        $params = [];
        foreach($inputs as $_ => $val) {
            $params[] = $val;
        }
        
        return $params;
    }
}