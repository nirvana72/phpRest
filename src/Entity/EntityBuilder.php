<?php
namespace PhpRest\Entity;

use PhpRest\Application;
use PhpRest\Annotation\AnnotationReader;
use PhpRest\Annotation\AnnotationBlock;
use PhpRest\Annotation\AnnotationTag;
use PhpRest\Entity\Annotation\ClassHandler;
use PhpRest\Entity\Annotation\TableHandler;
use PhpRest\Entity\Annotation\PropertyHandler;
use PhpRest\Entity\Annotation\FieldHandler;
use PhpRest\Entity\Annotation\PkHandler;
use PhpRest\Entity\Annotation\VarHandler;
use PhpRest\Entity\Annotation\RuleHandler;
use PhpRest\Exception\BadCodeException;
use phpDocumentor\Reflection\DocBlock\Tags\Var_ as VarTag;

class EntityBuilder
{
    /**
     * @Inject
     * @var \Doctrine\Common\Cache\Cache
     */
    private $cache;

    private $annotationHandlers = [
        [ClassHandler::class,     'class'],
        [TableHandler::class,     "class.children[?name=='table']"],
        [PropertyHandler::class,  'properties'],
        [FieldHandler::class,     "properties.*.children[?name=='field'][]"],
        [PkHandler::class,        "properties.*.children[?name=='pk'][]"],
        [VarHandler::class,       "properties.*.children[?name=='var'][]"],
        [RuleHandler::class,      "properties.*.children[?name=='rule'][]"],
    ];

    public function build($classPath) 
    {
        $cacheKey = 'EntityBuilder::build' . md5($classPath . Application::getInstance()->unionId);
        $entity = $this->cache->fetch($cacheKey);

        if ($entity === false || 
            $entity->modifyTimespan !== filemtime($entity->filePath)) {

            // echo "测试输出 entityBuilder::build {$classPath}<br>\r\n";
            $entity = new Entity($classPath);
            $classRef = new \ReflectionClass($classPath) or \PhpRest\abort(new BadCodeException("load class $classPath failed"));
            $entity->filePath = $classRef->getFileName();
            $entity->modifyTimespan = filemtime($entity->filePath);

            $annotationReader = $this->buildAnnotationReader($classRef);
            foreach ($this->annotationHandlers as $handler) {
                list($class, $expression) = $handler;
                $annotations = \JmesPath\search($expression, $annotationReader);
                if ($annotations !== null) {
                    if($expression === 'class') {
                        $annotations = [ $annotations ]; // class不会匹配成数组
                    }
                    foreach ($annotations as $annotation) {
                        $annotationHandler = new $class();
                        $annotationHandler($entity, $annotation);
                    }
                }
            }
            $this->cache->save($cacheKey, $entity);
        }

        return $entity;
    }

    /**
     * 解析entity文件
     * 
     * @param ReflectionClass $classRef entity反射类
     * @return AnnotationReader
     */
    private function buildAnnotationReader(\ReflectionClass $classRef): AnnotationReader
    {
        $reader = new AnnotationReader();
        $docComment = $classRef->getDocComment();
        if ($docComment === false) { 
            // entityClass 没写注解, 默认可以不写注解
            $reader->class = new AnnotationBlock();            
            $reader->class->summary = $classRef->getShortName();
        } else {
            $reader->class = $this->readAnnotationBlock($docComment);
        }
        $reader->class->position = 'class';
        
        // 遍历属性
        foreach ($classRef->getProperties(\ReflectionProperty::IS_PUBLIC) as $property) {
          
            // 过滤
            if ($property->isDefault() === false ||
                $property->isStatic()  === true || 
                $property->isPublic()  === false) { continue; }
            
            $block = $this->readAnnotationBlock($property->getDocComment());
            $block->name = $property->getName();
            $block->position = 'property';
            $reader->properties[$block->name] = $block;
        }

        while ($classRef = $classRef->getParentClass()) {
            foreach ($classRef->getProperties(\ReflectionProperty::IS_PUBLIC) as $i) {
                if ($i->isStatic()  === true || $i->isPublic()  === false) { continue; }

                $block = $this->readAnnotationBlock($i->getDocComment());
                $block->name = $i->getName();
                $block->position = 'property';
                $reader->properties[$block->name] = $block;
            }
        }

        return $reader;
    }

    /**
     * 解析注解块
     * 
     * @param string $docComment 注解内容
     * @return AnnotationBlock
     */
    private function readAnnotationBlock(string $docComment): AnnotationBlock
    {
        $annBlock = new AnnotationBlock();
        if ($docComment != false) {
            $factory = \phpDocumentor\Reflection\DocBlockFactory::createInstance();
            $docBlock = $factory->create($docComment);
            $annBlock->summary = $docBlock->getSummary();
            // $annBlock->description = $docBlock->getDescription()->render(); 属性只要摘要，不需要描述，收集了也没地方展示
            $tags = $docBlock->getTags(); 
            foreach ($tags as $tag) {
                $annTag = new AnnotationTag();
                $annTag->parent      = $annBlock;
                $annTag->name        = $tag->getName();
                if ($tag instanceof VarTag) {
                    $type = (string)$tag->getType();
                    $type = ltrim($type, '\\');
                    $annTag->description = $type;
                } else {
                    $desc = $tag->getDescription();
                    $annTag->description = isset($desc) ? $desc->render() : '';
                }
                $annBlock->children[] = $annTag;
            }
        }
        return $annBlock;
    }
}