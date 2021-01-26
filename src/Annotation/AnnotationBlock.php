<?php
namespace PhpRest\Annotation;

class AnnotationBlock extends AnnotationBase
{
    /**
     * 摘要
     * @var string
     */
    public $summary = '';

    /**
     * 描述
     * @var string
     */
    public $description = '';

    /**
     * 注解位置(class|method|property)
     * @var string
     */
    public $position = '';

    /**
     * @var AnnotationTag[]
     */
    public $children = [];

    /**
     * 其它信息
     * @var mixed
     */
    public $otherInfo;
}