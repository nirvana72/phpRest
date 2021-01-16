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
        // TODO 实体类继承支持
        $meta = new \PhpRest\Meta\PropertyMeta($ann->name);
        $meta->summary      = $ann->summary?: $ann->name;
        $meta->description  = $ann->description;
        
        $entity->properties[$ann->name] = $meta;
    }
}