<?php
namespace PhpRest\Annotation;

class AnnotationReader
{
    /**
     * @var AnnotationBlock
     */
    public $class;

    public static function build($classRef) {
        $docComment = $classRef->getDocComment();
        if (empty($docComment)) {
            return null;
        }
        $reader = new self();
        $reader->class = self::readAnnotationBlock($docComment);
        return $reader;
    }

    private static function readAnnotationBlock($docComment) {
        $factory = \phpDocumentor\Reflection\DocBlockFactory::createInstance();
        $docBlock = $factory->create($docComment);

        $annBlock = new AnnotationBlock();
        $annBlock->summary     = $docBlock->getSummary();
        $annBlock->description = $docBlock->getDescription()->render();
        $tags = $docBlock->getTags();
        foreach ($tags as $tag) {
            $annTag = new AnnotationTag();
            $annTag->parent      = $annBlock;
            $annTag->name        = $tag->getName();
            $annTag->description = $tag->getDescription()->render();
            $annBlock->children[] = $annTag;
        }
        return $annBlock;
    }
}