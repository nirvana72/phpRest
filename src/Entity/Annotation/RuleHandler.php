<?php
namespace PhpRest\Entity\Annotation;

use PhpRest\Entity\Entity;
use PhpRest\Annotation\AnnotationTag;
use PhpRest\Exception\BadCodeException;
use PhpRest\Application;

class RuleHandler
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

        if (strpos($ann->description, 'required') !== false) {
            $property->isOptional = false;
        }

        // 使用了验证规则模板
        if (0 === strpos($ann->description, 'template=')) {
            $template = str_replace('template=', '', $ann->description);
            $rules = Application::getInstance()->get("App.paramRules");
            if (array_key_exists($template, $rules)) {
                $ann->description = $rules[$template];
            } else {
                throw new BadCodeException("{$entity->classPath}::{$ann->name} 使用的规则模板 {$template} 不存在");
            }
        }

        $property->validation .= '|' . $ann->description;
        $property->validation = ltrim($property->validation, '|');
    }
}