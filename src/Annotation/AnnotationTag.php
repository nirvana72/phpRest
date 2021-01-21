<?php
namespace PhpRest\Annotation;

/**
 * 每一个 @开头的注解内容都是一个Tag
 */
class AnnotationTag extends AnnotationBase
{
    /**
     * 所属的block对象
     * @var AnnotationBlock|null
     */
    public $parent;

    /**
     * @var string
     */
    public $name = '';

    /**
     * when Tag instanceof TagWithType, val is array
     * @var string|array
     */
    public $description = '';
}