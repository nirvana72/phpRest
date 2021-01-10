<?php
namespace PhpRest\Annotation;

class AnnotationTag
{
    /**
     * @var AnnotationBlock|null
     */
    public $parent;

    /**
     * @var AnnotationBlock[]
     */
    public $children = [];

    /**
     * @var string
     */
    public $name = '';

    /**
     * @var string
     */
    public $description = '';
}