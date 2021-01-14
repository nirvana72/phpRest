<?php
namespace PhpRest\Entity\Annotation;

use PhpRest\Entity\Entity;
use PhpRest\Annotation\AnnotationTag;

class FieldHandler
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

        $array = explode('@', $ann->description);
        $property->field = $array[0];
        if ($array[1] === 'auto') {
            $property->autoIncrement = true;
        }
    }
}