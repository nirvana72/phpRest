<?php

namespace PhpRest\Meta;

/**
 * 属性对象
 */
class PropertyMeta
{
    /**
     * 参数名
     * @var string
     */
    public $name;

    /**
     * 摘要
     * @var string
     */
    public $summary;

    /**
     * 参数描述
     * @var string
     */
    public $description;

    /**
     * 参数类型[类型，实体类命名空间]
     * @var string[]
     */
    public $type = ['string', ''];

    /**
     * 验证规则
     * @var string
     */
    public $validation;

    /**
     * 是否可选(默认可选)
     * @var boolean
     */
    public $isOptional = true;

    /**
     * 对应数据库字段
     * @var string
     */
    public $field;

    /**
     * 是否主键
     * @var boolean
     */
    public $isPrimaryKey = false;

    /**
     * 是否自动增长键
     * @var boolean
     */
    public $isAutoIncrement = false;

    public function __construct($name) 
    {
        $this->name = $name;
        // 对应字段名默认为驼峰转下划线 可用 @field 重置
        $this->field = \PhpRest\uncamelize($name);
    }
}