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
     * 文件物理路径(验证缓存过期用)
     * @var string
     */
    public $filePath;

    /**
     * 上次修改时间(验证缓存过期用)
     * @var string
     */
    public $modifyTimespan;

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
     * @param $app Application
     * @param $data 数据
     * @return object
     */
    public function makeInstanceWithData($app, $data) {
        $obj = $app->make($this->classPath);
        foreach ($this->properties as $property) {
            $val = $data[$property->name];
            if (isset($val)) {
                // TODO 实体类数组属性支持
                if ($property->type[0] === 'Entity') {
                    // 嵌套实体类
                    $entityBuilder = $app->get(EntityBuilder::class);
                    $subEntity = $entityBuilder->build($property->type[1]);
                    $val = $subEntity->makeInstanceWithData($app, $val);
                } elseif($property->validation){
                    $vld = new Validator([$property->name => $val], [], 'zh-cn');
                    $vld->rule($property->validation, $property->name);
                    $vld->validate() or \PhpBoot\abort(current($vld->errors()));
                }
                // TODO 实体类基础类型数组 验证
                $obj->{$property->name} = $val;
            } else {
                $property->isOptional or \PhpRest\abort("实体类 {$this->classPath} 缺少属性 '{$property->name}'");
            }
        }
        return $obj;
    }
}