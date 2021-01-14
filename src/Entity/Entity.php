<?php
namespace PhpRest\Entity;

use PhpRest\Meta\PropertyMeta;
use PhpRest\Validator\Validator;

class Entity
{
    /**
     * @var string
     */
    public $summary = '';

    /**
     * @var string
     */
    public $description = '';

    /**
     * 对应的表名
     * @var string
     */
    public $table = '';

    /**
     * 类命名空间
     * @var string
     */
    public $classPath;

    /**
     * 属性集合
     * @var PropertyMeta[]
     */
    public $properties = [];

    /**
     * @param string $classPath 实体类的命名空间
     */
    public function __construct($classPath) 
    {
        $this->classPath = $classPath;

        // 表名默认为类名驼峰转下划线， 可以由@table 重置
        $shortName = end(explode('\\', $classPath));
        $this->table = \PhpRest\uncamelize($shortName);
    }

    /**
     * 获取指定名称的属性
     * 
     * @param $name
     * @return PropertyMeta|false
     */
    public function getProperty($name) 
    {
        if (array_key_exists($name, $this->properties)){
            return $this->properties[$name];
        }
        return false;
    }

    /**
     * 创建实体
     * 
     * @param $data 数据
     * @return object
     */
    public function makeInstanceWithData($data) {
        \Valitron\Validator::lang('zh-cn');
        $obj = new $this->classPath();
        foreach ($this->properties as $meta) {
            $val = $data[$meta->name];
            if (isset($val)) {
                if($meta->validation){
                    $vld = new Validator([$meta->name => $val]);
                    $vld->rule($meta->validation, $meta->name);
                    if (false === $vld->validate()) {
                        $error = $vld->errors();
                        \PhpRest\abort($error[$meta->name][0]);
                    }
                }
                $obj->{$meta->name} = $val;
            } else {
                $meta->isOptional or \PhpRest\abort("实体类 {$this->classPath} 缺少属性 '{$meta->name}'");
            }
        }
        return $obj;
    }
}