<?php
namespace PhpRest\Entity\Annotation;

use PhpRest\Entity\Entity;
use PhpRest\Annotation\AnnotationBlock;

class PropertyHandler
{
    /**
     * @param Entity $entity
     * @param AnnotationBlock $ann
     */
    public function __invoke(Entity $entity, AnnotationBlock $ann) 
    {
        $meta = new \PhpRest\Meta\PropertyMeta($ann->name);
        $meta->summary = $ann->summary?: $ann->name;
        $entity->properties[$ann->name] = $meta;
    }
}