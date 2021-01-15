<?php
namespace PhpRest\Utils;

use phpDocumentor\Reflection\TypeResolver;
use phpDocumentor\Reflection\Types\ContextFactory;

class ReflectionHelper
{
    /**
     * @param string $contextClassPath 上下文类命名空间
     * @param string $subClassName     上下文中的引用类名
     * @return string 引用类的全命名空间
     */
    public static function resolveFromReflector($contextClassPath, $subClassName) 
    {
        $resolver = new TypeResolver();
        $contextFactory = new ContextFactory();
        $classRef = new \ReflectionClass($contextClassPath);
        $context = $contextFactory->createFromReflector($classRef);
        $typeRef = $resolver->resolve($subClassName, $context);
        $type = (string)$typeRef;
        $type = ltrim($type, '\\');
        return $type;
    }
}