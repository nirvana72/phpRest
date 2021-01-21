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
     * @param string $ruleStr
     * @param string $fields
     */
    public function rule($ruleStr, $fields) 
    {
        $ruleAry = explode('|', $ruleStr);
        foreach($ruleAry as $r) {
            $ary = explode('=', trim($r));
            $rule = $ary[0];
            if (count($ary) === 2) {
                $params = $ary[1];
                if ($rule !== 'regex' && false !== strpos($params, ',')) {
                    $params = explode(',', $params);
                    if ($rule === 'in' || $rule === 'notIn') {
                       $params = [$params];
                    }
                } else {
                    $params = [$params];
                }
                // $v->rule('notIn', 'color', ['blue', 'green', 'red', 'yellow']);
                // $v->rule('lengthBetween', 'username', 1, 10);
                // $v->rule('length', 'username', 10);
                $params = array_merge([$rule, $fields], $params);
                call_user_func_array([$this, 'parent::rule'], $params);
            } else {
                parent::rule($rule, $fields);
            }
        }
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