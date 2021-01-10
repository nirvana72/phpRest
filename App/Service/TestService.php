<?php
namespace App\Service;

class TestService
{
    /**
    * @Inject("env")
    */
    private $env;
    
    public function test($id, $name) {
        echo "TestService.test, id = {$id}, name = {$name}, env = {$this->env}";
    }
}