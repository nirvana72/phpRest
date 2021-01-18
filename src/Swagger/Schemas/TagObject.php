<?php
namespace PhpRest\Swagger\Schemas;

class TagObject
{
    /** @var string */
    public $name = '';

    /** @var string */
    public $description = '';

    /** @var ExternalDocsObject */
    public $externalDocs;
}