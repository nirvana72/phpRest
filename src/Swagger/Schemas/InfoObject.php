<?php
namespace PhpRest\Swagger\Schemas;

class InfoObject
{
    /** @var string */
    public $description = '';

    /** @var string */
    public $version = '1.0.0';

    /** @var string */
    public $title = 'Swagger PhpRest';

    /** @var string */
    public $termsOfService = 'http://swagger.io/terms/';

    /** @var ContactObject */
    public $contact;

    /** @var LicenseObject */
    public $license;
}