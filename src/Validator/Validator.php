<?php
namespace PhpRest\Validator;

/**
 * Validator
 *
 * ** usage: **
 *  $v = new Validator();
 *  $v->rule('required|integer|in:1,2,3', 'fieldName');
 *
 * ** rules: **
 * see https://github.com/vlucas/valitron#built-in-validation-rules
 */
class Validator extends \Valitron\Validator
{
    /**
     * @param callable|string $rule
     * @param array|string $fields
     * @return $this
     */
    public function rule($rule, $fields) 
    {
        if(is_string($rule)){
            $rules = explode('|', $rule);
            foreach ($rules as $r){
                $params = explode('=', trim($r));
                $rule = $params[0];
                $params = isset($params[1])?explode(',', $params[1]):[];
                if($rule == 'in' || $rule == 'notIn'){
                    $params = [$params];
                }
                call_user_func_array([$this, 'parent::rule'], array_merge([$rule, $fields], $params));
            }
            return $this;
        }
        parent::rule($rule, $fields);
        return $this;
    }

    /**
     * 转化框架支持的参数类型为基础类型
     * 
     * @param string $type
     * @return [基础类型，验证规则，默认描述]
     */
    public static function typeCast($type) {
        if(in_array($type, ['int', 'integer'])) {
            return ['integer', 'integer', 1];
        }
        elseif($type === 'dateTime') {
            return ['string', 'dateFormat=Y-m-d H:i:s', 'yyyy-mm-dd HH:mm:ss'];
        }
        elseif($type === 'date') {
            return ['string', 'date', 'yyyy-mm-dd'];
        }
        elseif($type === 'time') {
            return ['string', 'dateFormat=H:i:s', 'HH:mm:ss'];
        }
        elseif($type === 'numeric') {
            return ['number', 'numeric', 1.1];
        }
        elseif($type === 'slug') {
            return ['string', 'slug', '只能包括英文字母(a-z)、数字(0-9)、破折号和下划线'];
        }
        elseif($type === 'alpha') {
            return ['string', 'alpha', '只能包括英文字母(a-z)'];
        }
        elseif($type === 'alphaNum') {
            return ['string', 'alphaNum', '只能包括英文字母(a-z)和数字(0-9)'];
        }
        elseif(in_array($type, ['email', 'url'])) {
            return ['string', $type, $type];
        }
        return [$type, '', ''];
    }
}