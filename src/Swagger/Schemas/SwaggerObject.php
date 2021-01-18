<?php
namespace PhpRest\Swagger\Schemas;

class SwaggerObject
{
    /** @var string */
    public $swagger = '2.0';

    /** @var InfoObject */
    public $info;

    /** @var string */
    public $host = 'localhost';

    /** @var string */
    public $basePath = '';

    /** @var TagObject[] */
    public $tags = [];

    /** @var string[] */
    public $schemes = ['https', 'http'];

    /** @var array ['uri' => ['method' => pathObject, ....] ...] */
    public $paths = [];

    /** @var array */
    public $securityDefinitions = [];

    /** @var array */
    public $definitions = [];

    /** @var ExternalDocsObject */
    public $externalDocs;
}