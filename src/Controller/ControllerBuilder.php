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
use Doctrine\Common\Cache\Cache;

class ControllerBuilder
{
    /**
     * @Inject
     * @var \Doctrine\Common\Cache\Cache
     */
    private $cache;

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
        $cacheKey = 'controllerBuilder::build' . md5($classPath);
        $controller = $this->cache->fetch($cacheKey);

        if ($controller === false || 
            $controller->modifyTimespan !== filemtime($controller->filePath)) {
           
            // echo "测试输出：controllerBuilder::build {$classPath}<br>\r\n";
            $controller = new Controller($classPath);
            $classRef = new \ReflectionClass($classPath) or \PhpRest\abort("load class $classPath failed");
            $controller->filePath = $classRef->getFileName();
            $controller->modifyTimespan = filemtime($controller->filePath);
            
            $annotationReader = $this->buildAnnotationReader($classRef);
            foreach ($this->annotationnHandlers as $handler) {
                list($class, $expression) = $handler;
                $annotations = \JmesPath\search($expression, $annotationReader);
                if ($annotations !== null) {
                    if($expression === 'class'){
                        $annotations = [ $annotations ]; // class不会匹配成数组
                    }
                    foreach ($annotations as $annotation){
                        $annotationHandler = new $class();
                        $annotationHandler($controller, $annotation);
                    }
                }
            }
            $this->cache->save($cacheKey, $controller);
        }

        return $controller;
    }

    /**
     * 解析controller文件 class 及 function 上的注解
     * 
     * @param ReflectionClass $classRef controller反射类
     * @return AnnotationReader
     */
    private function buildAnnotationReader($classRef) 
    {
        $reader = new AnnotationReader();
        $docComment = $classRef->getDocComment();

        if ($docComment === false) { 
            // class 没写注解, 默认可以不写注解
            $reader->class = new AnnotationBlock();
            $reader->class->summary = $classRef->getName();
        } else {
            $reader->class = $this->readAnnotationBlock($docComment);
        }
        
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
     * 解析注解块
     * 
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