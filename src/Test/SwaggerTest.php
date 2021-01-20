<?php
namespace PhpRest\Test;

class SwaggerTest
{
    

    public function test1() {
        $swagger = $this->app->get(\PhpRest\Swagger\SwaggerHandler::class);
        $swagger->build();
    }
}