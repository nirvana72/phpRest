<?php
namespace App\Controller;

/**
 * Index
 * 
 * @path /index
 */
class IndexController
{
    // 这个方法没写注解，不会被加载
    public function index() 
    {
        echo $this->env;
    }

    /**
     * test1
     *
     * @route GET /test1
     */
    public function test1() 
    {
        echo "IndexController.test1 <br>";
    }

    /**
     * test2
     *
     * @route GET /test2
     * @param int $p1 p1 {@v integer}
     * @param string $p2 p2
     * @param string $p3 p3
     */
    public function test2($p1, $p3, $p2) 
    {
        echo "IndexController.test2 <br>";
        echo "p1={$p1}, p2={$p2}, p3={$p3}";
    }
}