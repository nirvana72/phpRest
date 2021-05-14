<?php
namespace PhpRest\Entity\Annotation;

use PhpRest\Entity\Entity;
use PhpRest\Annotation\AnnotationTag;
use PhpRest\Exception\BadCodeException;

class VarHandler
{
    /**
     * @param Entity $entity
     * @param AnnotationTag $ann
     */
    public function __invoke(Entity $entity, AnnotationTag $ann) 
    {
        $target = $ann->parent->name;
        $property = $entity->getProperty($target);
        
        if ($property === false) { return; }

        // 判断实体类属性是否嵌套实体类
        $type = $ann->description;
        if (strpos($type, '\\') !== false || preg_match("/^[A-Z]{1}$/", $type[0])) {
            $entityClassPath = $type;
            $type = 'Entity';
            if (substr($entityClassPath, -2) === '[]') {
                $entityClassPath = substr($entityClassPath, 0, -2);
                $type = 'Entity[]';
            }
            if (strpos($entityClassPath, '\\') === false) {
                // 如果没写全命名空间，需要通过反射取得全命名空间
                $entityClassPath = \PhpRest\Utils\ReflectionHelper::resolveFromReflector($entity->classPath, $entityClassPath);
            }
            class_exists($entityClassPath) or \PhpRest\abort(new BadCodeException("{$entity->classPath} 属性 {$ann->name} 指定的实体类 {$entityClassPath} 不存在"));
            $property->type = [$type, $entityClassPath];
        } else {
            $property->type = [$type, ''];
            $property->validation = \PhpRest\Validator\Validator::ruleCast($type);
        }
    }
}