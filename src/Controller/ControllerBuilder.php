<?php
namespace PhpRest\Controller;

use PhpRest\Annotation\AnnotationReader;
use PhpRest\Annotation\AnnotationBlock;
use PhpRest\Annotation\AnnotationTag;
use PhpRest\Annotation\AnnotationTagsOutput;
use PhpRest\Controller\Annotation\ClassHandler;
use PhpRest\Controller\Annotation\PathHandler;
use PhpRest\Controller\Annotation\RouteHandler;
use PhpRest\Controller\Annotation\ParamHandler;
use PhpRest\Controller\Annotation\ReturnHandler;
use PhpRest\Controller\Annotation\BindHandler;
use PhpRest\Controller\Annotation\ValidateHandler;
use PhpRest\Controller\Annotation\HookHandler;
use phpDocumentor\Reflection\DocBlock\DescriptionFactory;
use phpDocumentor\Reflection\DocBlock\StandardTagFactory;
use phpDocumentor\Reflection\DocBlockFactory;
use phpDocumentor\Reflection\FqsenResolver;
use phpDocumentor\Reflection\TypeResolver;

class ControllerBuilder
{
    private $annotationnHandlers = [
        [ClassHandler::class, 'class'],
        [PathHandler::class, "class.children[?name=='path']"],
        [RouteHandler::class, "methods.*.children[?name=='route'][]"],
        [ParamHandler::class, "methods.*.children[?name=='param'][]"],
        [ReturnHandler::class, "methods.*.children[?name=='return'][]"],
        [BindHandler::class, "methods.*.children[].children[?name=='bind'][]"],
        [ValidateHandler::class, "methods.*.children[].children[?name=='v'][]"],
        [HookHandler::class, "methods.*.children[?name=='hook'][]"],
    ];

    public function build($classPath) 
    {
        $controller = new Controller($classPath);
        $classRef = new \ReflectionClass($classPath) or \PhpRest\abort("load class $classPath failed");
        $annotationReader = $this->buildAnnotationReader($classRef);
        if ($annotationReader !== null) {
            foreach ($this->annotationnHandlers as $handler) {
                list($class, $expression) = $handler;
                $annotations = \JmesPath\search($expression, $annotationReader);
                if ($annotations !== null) {
                  if($expression === 'class'){
                      $annotations = [ $annotations ]; // class不会匹配成数组
                  }
                  foreach ($annotations as $annotation){
                      $annotationHandler = new $class(); // 实例化 $annotationnHandlers[XXXHalder]
                      $annotationHandler($controller, $annotation);
                  }
                }
            }
        }
        return $controller;
    }

    private function buildAnnotationReader($classRef) 
    {
        $docComment = $classRef->getDocComment();
        // class 没写注解
        if ($docComment === false) { return null; }

        $reader = new AnnotationReader();
        $reader->class = $this->readAnnotationBlock($docComment);
        // 遍历controller下的方法
        foreach ($classRef->getMethods() as $method) {
            $docComment = $method->getDocComment();
            // 过滤掉method没写注解, 或不是public 的方法
            if ($docComment === false || 
                $method->isStatic() === true || 
                $method->isPublic() === false) { continue; }
            
            $block = $this->readAnnotationBlock($docComment);
            $block->name = $method->getName();
            $reader->methods[$block->name] = $block;
        }

        return $reader;
    }

    /**
     * @param string $docComment 注解内容
     * @return object
     */
    private function readAnnotationBlock($docComment) 
    {
        $factory = $this->createDocBlockFactory(); //\phpDocumentor\Reflection\DocBlockFactory::createInstance();
        $docBlock = $factory->create($docComment);

        $annBlock = new AnnotationBlock();
        $annBlock->summary     = $docBlock->getSummary();
        $annBlock->description = $docBlock->getDescription()->render();
        $tags = $docBlock->getTags(); 
        foreach ($tags as $tag) {
            $desc = $tag->getDescription();
            $annTag = new AnnotationTag();
            $annTag->parent      = $annBlock;
            $annTag->name        = $tag->getName();
            $annTag->description = $desc->render();
            $annBlock->children[] = $annTag;
            if ($desc) {
                $output = new AnnotationTagsOutput();
                $desc->render($output);
                foreach ($output->tags as $child) {
                    $childTag = new AnnotationTag();
                    $childTag->parent = $annTag;
                    $childTag->name = $child->getName();
                    $childTag->description = $child->getDescription()->render();
                    $annTag->children[] = $childTag;
                }
            }
        }
        return $annBlock;
    }

    private function createDocBlockFactory()
    {
        $fqsenResolver = new FqsenResolver();
        $tagFactory = new StandardTagFactory($fqsenResolver,[]);
        $descriptionFactory = new DescriptionFactory($tagFactory);
        $tagFactory->addService($descriptionFactory);
        $tagFactory->addService(new TypeResolver($fqsenResolver));
        $docBlockFactory = new DocBlockFactory($descriptionFactory, $tagFactory);
        return $docBlockFactory;
    }
}