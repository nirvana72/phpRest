<?php
namespace PhpRest\Entity\Annotation;

use PhpRest\Entity\Entity;
use PhpRest\Annotation\AnnotationBlock;

class ClassHandler
{
    /**
     * @param Entity $entity
     * @param AnnotationBlock $ann
     */
    public function __invoke(Entity $entity, AnnotationBlock $ann) 
    {
        $entity->summary     = $ann->summary;
        $entity->description = $ann->description;
    }
}