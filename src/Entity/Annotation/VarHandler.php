<?php
namespace PhpRest\Entity\Annotation;

use PhpRest\Entity\Entity;
use PhpRest\Annotation\AnnotationTag;

class VarHandler
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

        // 判断实体类属性是否嵌套实体类
        $type = $ann->description;
        if (strpos($type, '\\') !== false || preg_match("/^[A-Z]{1}$/", $type[0])) {
            $realType = 'Entity';
            if (substr($type, -2) === '[]') {
                $type = substr($type, 0, -2);  
                $realType = 'Entity[]';                  
            }
            if (strpos($type, '\\') === false) {
                // 如果没写全命名空间，需要通过反射取得全命名空间
                $type = \PhpRest\Utils\ReflectionHelper::resolveFromReflector($entity->classPath, $type);
            }
            class_exists($type) or \PhpRest\abort("{$entity->classPath} 属性 {$ann->name} 指定的实体类 {$type} 不存在");
            $property->type = [$realType, $type];
        } else {
            $cast = \PhpRest\Validator\Validator::typeCast($type);
            list($realType, $validation, $desc) = $cast;
            $property->validation = $validation;
            $property->type = [$realType, $desc];
        }
    }
}