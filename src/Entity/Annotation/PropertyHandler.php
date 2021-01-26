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
        $meta->summary = $ann->summary?: $ann->name;
        $type = $ann->otherInfo['type'];
        if (isset($type)) {
            $meta->type = [$type, ''];
            if ($meta->type[0] === 'int')   { $meta->validation = 'integer'; } // public int $p1;
            if ($meta->type[0] === 'float') { $meta->validation = 'numeric'; } // public float $p1;
            if ($meta->type[0] === 'array') { $meta->type[0] = 'string[]'; } // public array $p1;
        }
        $entity->properties[$ann->name] = $meta;
    }
}