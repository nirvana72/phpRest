<?php
namespace PhpRest\Annotation;

class AnnotationReader extends AnnotationBase
{
    /**
     * class 上的注解对象
     * @var AnnotationBlock
     */
    public $class;

    /**
     * 方法上的注解对象
     * @var AnnotationBlock[]
     */
    public $methods = [];
}