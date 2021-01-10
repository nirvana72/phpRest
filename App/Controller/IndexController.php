<?php
namespace App\Controller;

/**
 * Index
 * 
 * @path /index
 */
class IndexController
{
    /**
    * @Inject("env")
    */
    private $env;

    /**
     * @Inject
     * @var \App\Service\TestService
     */
    private $testService;

    public function index() {
        echo $this->env;
    }

    /**
     * test
     *
     * @route GET /test
     * @param int $id id
     * @param string $name name
     */
    public function test($id, $name) {
        echo "IndexController.test, id = {$id}, name = {$name}, env = {$this->env}";
        $this->testService->test($id, $name);
    }
}