<?php
namespace PhpRest\Entity\Annotation;

use PhpRest\Entity\Entity;
use PhpRest\Annotation\AnnotationTag;

class VarHandler
{
    /**
     * @param Entity $container
     * @param AnnotationTag $ann
     */
    public function __invoke(Entity $entity, AnnotationTag $ann) 
    {
        $target = $ann->parent->name;
        $property = $entity->getProperty($target);
        
        if ($property === false) { return; }

        // TODO 实体类嵌套实体类支持

        $cast = \PhpRest\Validator\Validator::typeCast($ann->description);
        list($realType, $validation, $desc) = $cast;
        $property->validation = $validation;
        $property->type = [$realType, $desc];
    }
}