<?php
namespace PhpRest\Entity\Annotation;

use PhpRest\Entity\Entity;
use PhpRest\Annotation\AnnotationBlock;

class PropertyHandler
{
    /**
     * @param Entity $container
     * @param AnnotationBlock $ann
     */
    public function __invoke(Entity $entity, AnnotationBlock $ann) 
    {
        $meta = new \PhpRest\Meta\PropertyMeta($ann->name);
        $meta->summary      = $ann->summary;
        $meta->description  = $ann->description;
        
        $entity->properties[$ann->name] = $meta;
    }
}