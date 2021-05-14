<?php
namespace PhpRest\Entity\Annotation;

use PhpRest\Entity\Entity;
use PhpRest\Annotation\AnnotationTag;

class TableHandler
{
    /**
     * @param Entity $entity
     * @param AnnotationTag $ann
     */
    public function __invoke(Entity $entity, AnnotationTag $ann) 
    {
        $entity->table = $ann->description;
    }
}