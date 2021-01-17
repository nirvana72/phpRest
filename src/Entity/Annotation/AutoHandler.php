<?php
namespace PhpRest\Entity\Annotation;

use PhpRest\Entity\Entity;
use PhpRest\Annotation\AnnotationTag;

class AutoHandler
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

        $property->isAutoIncrement = true;
    }
}