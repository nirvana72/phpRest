<?php
namespace PhpRest\Controller\Annotation;

use PhpRest\Controller\Controller;
use PhpRest\Annotation\AnnotationTag;
use PhpRest\Exception\BadCodeException;
use PhpRest\Application;

class ParamHandler
{
    /**
     * @param Controller $controller
     * @param AnnotationTag $ann
     */
    public function __invoke(Controller $controller, AnnotationTag $ann) 
    {
        $method = $ann->parent->name;
        $route = $controller->getRoute($method);
        if ($route === false) { return; }
        list($paramType, $paramName, $paramDesc) = $ann->description;

        $paramMeta = $route->requestHandler->getParamMeta($paramName);
        $paramMeta or \PhpRest\abort(new BadCodeException("{$controller->getClassName()}::{$method} 注解参数 {$paramName} 没有被使用"));

        if ($paramMeta->type[0] === 'Entity') { // 这个在RouteHandler中, 如果 function(User $user), type 赋值为Entity
            if (empty($paramType) === false) {
                // 绑定实体类参数，@param 可以不写类型，默认按参数描述指定
                // 但是 @param 如果写了类型，就需要验证类型一至
                $paramTypeInMethodName = strpos($paramType, '\\') !== false ? $paramMeta->type[1] : end(explode('\\', $paramMeta->type[1]));
                $paramType === $paramTypeInMethodName or \PhpRest\abort(new BadCodeException("{$controller->getClassName()}::{$method} 实体类参数 {$paramName} 与@param描述不一至"));
            }
        }
        else {
            // function 中没有指定参数类型，判断 @param 中指定是否为实体类
            // 带 \ 命名空间 或首字母大写， 认为是实体类
            if (strpos($paramType, '\\') !== false || preg_match("/^[A-Z]{1}$/", $paramType[0])) {
                $entityClassPath = $paramType;
                $paramType = 'Entity';
                if (substr($entityClassPath, -2) === '[]') {
                    $entityClassPath = substr($entityClassPath, 0, -2);  
                    $paramType = 'Entity[]';                  
                }
                if (strpos($entityClassPath, '\\') === false) {
                    // 如果没写全命名空间，需要通过反射取得全命名空间
                    $entityClassPath = \PhpRest\Utils\ReflectionHelper::resolveFromReflector($controller->classPath, $entityClassPath);
                }
                class_exists($entityClassPath) or \PhpRest\abort(new BadCodeException("{$controller->getClassName()}::{$method} @param {$paramName} 指定的实体类 {$entityClassPath} 不存在"));
                $paramMeta->type = [$paramType, $entityClassPath];
            } else {
                // 否则作为基础类型处理
                if (! empty($paramType)) {
                    $paramMeta->type = [$paramType, ''];
                    $paramMeta->validation = \PhpRest\Validator\Validator::ruleCast($paramType);
                }
            }
        }

        list($ret, $tagContent, $paramDesc) = $this->loadInlineTag('bind', $paramDesc);
        $ret >= 0 or \PhpRest\abort(new BadCodeException("{$controller->getClassName()}::{$method} 参数验证描述 bind 格式不正确"));
        if ($ret === 1) {
            if (strpos($tagContent, 'path.')) $tagContent = str_replace('path.', 'attributes.', $tagContent);
            $paramMeta->source = $tagContent;
        }

        list($ret, $tagContent, $paramDesc) = $this->loadInlineTag('rule', $paramDesc);
        $ret >= 0 or \PhpRest\abort(new BadCodeException("{$controller->getClassName()}::{$method} 参数验证描述 rule 格式不正确"));
        if ($ret === 1) {
            // 使用了验证规则模板
            if (0 === strpos($tagContent, 'template=')) {
                $template = str_replace('template=', '', $tagContent);
                $rules = Application::getInstance()->get("App.paramRules");
                if (array_key_exists($template, $rules)) {
                    $tagContent = $rules[$template];
                } else {
                    throw new BadCodeException("{$controller->getClassName()}::{$method} 使用的规则模板 {$template} 不存在");
                }
            }
            $paramMeta->validation .= "|{$tagContent}";
            $paramMeta->validation = ltrim($paramMeta->validation, '|');
        }

        if ($paramDesc !== '') $paramMeta->description = $paramDesc;
    }

    // $desc = 'p1 {@bind request.user} {@rule regax=/^[a-z]{5,10}$/}';
    private function loadInlineTag($tagName, $desc): array
    {
        $tag = '';
        $tagName = '{@' . $tagName;
        $stIndex = strpos($desc, $tagName);
        if ($stIndex !== false) {
            $len = strlen($desc);
            $enIndex = $stIndex + 1;
            $braceNum = 1;
            while ($braceNum > 0 && $enIndex < $len) {
                $char = $desc[$enIndex];
                if ($char === '{') $braceNum++;
                if ($char === '}') $braceNum--;
                $enIndex++;
            }
            if ($braceNum === 0) {
                $tag = substr($desc, $stIndex, ($enIndex - $stIndex));
            } else {
                return [-1, null, $desc];
            }
        }
        if ($tag !== '') {
            $desc = trim(str_replace($tag, '', $desc));
            $tag = trim(substr($tag, strlen($tagName), -1));
            return [1, $tag, $desc];
        }
        return [0, null, $desc];
    }
}