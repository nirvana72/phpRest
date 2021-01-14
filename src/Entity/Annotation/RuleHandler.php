<?php
namespace PhpRest\Entity\Annotation;

use PhpRest\Entity\Entity;
use PhpRest\Annotation\AnnotationTag;

class RuleHandler
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

        if (strpos($ann->description, 'required') !== false) {
            $property->isOptional = false;
        }

        $property->validation .= '|' . $ann->description;
        $property->validation = ltrim($property->validation, '|');
    }
}