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
     * @param string $type
     * @return 验证规则
     */
    public static function ruleCast($type) {
        if(empty($type)) {
            return '';
        }
        if(substr($type, -2) === '[]') {
            $type = substr($type, 0, -2);
        }
        if($type === 'int') {
            return 'integer';
        }
        elseif($type === 'dateTime') {
            return 'dateFormat=Y-m-d H:i:s';
        }
        elseif($type === 'time') {
            return 'dateFormat=H:i:s';
        }
        elseif(in_array($type, ['integer', 'date', 'numeric', 'slug', 'alpha', 'alphaNum', 'email', 'url', 'ip'])) {
            return $type;
        }
        return '';
    }
}