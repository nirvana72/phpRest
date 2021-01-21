<?php
namespace PhpRest\Annotation;

/**
 * 实现 ArrayAccess, 让对象具有数组访问能力, 以便支持\JmesPath\search
 */
class AnnotationBase implements \ArrayAccess
{
    /**
     * override
     */
    public function offsetExists($offset) 
    {
        return isset($this->$offset);
    }

    /**
      * override
      */
    public function offsetGet($offset) 
    {
        return $this->$offset;
    }

    /**
      * override
      */
    public function offsetSet($offset, $value) 
    {
        $this->$offset = $value;
    }

    /**
      * override
      */
    public function offsetUnset($offset) 
    {
        unset($this->$offset);
    }
}