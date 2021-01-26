<?php
namespace PhpRest\Entity\Annotation;

use PhpRest\Entity\Entity;
use PhpRest\Annotation\AnnotationTag;
use PhpRest\Exception\BadCodeException;

class PkHandler
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

        $property->isPrimaryKey = true;
        $property->isAutoIncrement = $ann->description === 'auto';
        if ($property->isAutoIncrement && $property->type[0] !== 'int') {
            throw new BadCodeException("{$entity->classPath} 属性 {$property->name} 自增类型必须是int");
        }
    }
}