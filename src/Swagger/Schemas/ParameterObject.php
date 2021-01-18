<?php
namespace PhpRest\Swagger\Schemas;

class ParameterObject
{
    /** @var string */
    public $name = '';

    /** @var string */
    public $in = '';

    /** @var string */
    public $description = '';

    /** @var bool */
    public $required = true;

    /** @var string */
    public $type = 'string';
}