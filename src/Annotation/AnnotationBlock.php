<?php
namespace PhpRest\Annotation;

class AnnotationBlock extends AnnotationBase
{
    /**
     * 类名或方法名
     * @var string
     */
    public $name = '';

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
}