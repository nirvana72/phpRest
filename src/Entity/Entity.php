<?php
namespace PhpRest\Entity;

use PhpRest\Application;
use PhpRest\Meta\PropertyMeta;
use PhpRest\Validator\Validator;
use PhpRest\Exception\BadArgumentException;

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
    public function __construct(string $classPath)
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
     * @param array  $data          数据
     * @param bool   $withValidator 是否需要验证
     * @param object $obj           引用
     * @return object
     */
    public function makeInstanceWithData($data, $withValidator = true, &$obj = null) {
        // 实例化为一个实体类的对象，必需是一个关联数组
        \PhpRest\isAssocArray($data) or \PhpRest\abort(new BadArgumentException("数据源不是一个对象结构, 不能实例化成一个实体类"));
        if ($obj === null) $obj = Application::getInstance()->make($this->classPath);
        foreach ($this->properties as $property) {
            $val = $data[$property->name];
            if (isset($val)) {
                if ($property->type[0] === 'Entity' || $property->type[0] === 'Entity[]') {
                    $entityClassPath = $property->type[1];
                    $entity = Application::getInstance()->get(EntityBuilder::class)->build($entityClassPath);
                    if ($property->type[0] === 'Entity[]') {
                        is_array($val) or \PhpRest\abort(new BadArgumentException("数据源 '{$property->name}' 不是数组"));
                        $ary = [];
                        foreach($val as $d) {
                            $ary[] = $entity->makeInstanceWithData($d, $withValidator);
                        }
                        $val = $ary;
                    } else {
                        $val = $entity->makeInstanceWithData($val, $withValidator);
                    }
                } elseif($withValidator){
                    $needArray = substr($property->type[0], -2) === '[]';
                    if ($needArray) {
                        is_array($val) or \PhpRest\abort(new BadArgumentException("数据源 '{$property->name}' 不是数组"));
                    }
                    if ($property->validation) {
                        $fields = $needArray ? "{$property->name}.*" : $property->name;
                        $vld = new Validator([$property->name => $val], [], 'zh-cn');
                        $vld->rule($property->validation, $fields);
                        $vld->validate() or \PhpRest\abort(new BadArgumentException(current($vld->errors())[0]));
                    }
                }
                $obj->{$property->name} = $val;
            } else {
                $property->isOptional or \PhpRest\abort(new BadArgumentException("实体类 {$this->classPath} 缺少属性 '{$property->name}'"));
            }
        }
        return $obj;
    }
}