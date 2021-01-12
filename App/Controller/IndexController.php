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

    // /**
    //  * test1
    //  *
    //  * @route GET /test
    //  */
    // public function test() 
    // {
    //     echo "IndexController.test <br>";
    // }

    // /**
    //  * test
    //  *
    //  * @route GET /test
    //  * @param int $p1 p1
    //  * @param string $p2 p2
    //  */
    // public function test($p2, $p1) 
    // {
    //     echo "p1={$p1}, p2={$p2}";
    // }

    // /**
    //  * test
    //  *
    //  * @route GET /test
    //  * @param int $p1 p1
    //  * @param string $p2 p2
    //  */
    // public function test($p2, $p1, $p3) 
    // {
    //     echo "p1={$p1}, p2={$p2}, p3={$p3}";
    // }

    /**
     * test
     *
     * @route GET /test
     * @param int $p1 p1
     * @param string $p2 p2 {@v length:6}
     * @param string $p3 p3 {@v length:6}
     */
    public function test($p2, $p1, $p3) 
    {
        $rows = [
          [
            'id' => 1,
            'name' => 'nijia1'
          ],
          [
            'id' => 3,
            'name' => 'nijia2'
          ],
          [
            'id' => 4,
            'name' => 'nijia3'
          ],
          [
            'id' => 5,
            'name' => 'nijia4'
          ],
          [
            'id' => 6,
            'name' => 'nijia5'
          ]
        ];
        $res = [
          'ret' => 1,
          'msg' => 'success',
          'data' => [
            'totla' => 5,
            'list' => $rows
          ]
        ];
        return $res; //"p1={$p1}, p2={$p2}, p3={$p3}";
    }
}