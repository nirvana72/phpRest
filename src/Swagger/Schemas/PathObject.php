<?php
namespace PhpRest\Swagger\Schemas;

class PathObject
{
    /** @var string[] */
    public $tags = [];

    /** @var string */
    public $summary = '';

    /** @var string */
    public $description = '';

    /** @var string */
    public $operationId = '';

    /** @var string[] */
    public $consumes = ['application/json', 'application/xml'];

    /** @var string[] */
    public $produces = ['application/json', 'application/xml'];

    /** @var parameterObject[] */
    public $parameters = [];

    /** @var array ['status' => $responseObject ...] */
    public $responses = [];

    /** @var array */
    public $security = [];
}