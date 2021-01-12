<?php

namespace PhpRest\Meta;

/**
 * 参数对象
 */
class ParamMeta
{
    /**
     * 参数名
     * @var string
     */
    public $name;

    /**
     * 参数类型
     * @var string
     */
    public $type;

    /**
     * 默认值
     * @var mixed|null
     */
    public $default;

    /**
     * 是否可选参数
     * @var boolean
     */
    public $isOptional;

    /**
     * 验证描述
     * @var string
     */
    public $validation;

    /**
     * 参数描述
     * @var string
     */
    public $description;
}