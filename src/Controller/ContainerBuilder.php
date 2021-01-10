<?php
namespace PhpRest\Controller;

use PhpRest\Annotation\AnnotationReader;

class ContainerBuilder
{
    public function build($className) {
        $classRef = new \ReflectionClass($className) or \PhpRest\abort("load class $className failed");
        $annotation = AnnotationReader::build($classRef);

        var_dump($annotation);
    }
}