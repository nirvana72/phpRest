<?php
namespace PhpRest\Test;

use PhpRest\Application;

class EntityBuildTest
{
    public function test1() {
        $builder = Application::getInstance()->get(\PhpRest\Entity\EntityBuilder::class);
        $entity = $builder->build('App\Entity\SysUser');
        \PhpRest\dump($entity);
    }

    public function test2() {
        $builder = Application::getInstance()->get(\PhpRest\Entity\EntityBuilder::class);
        $entity = $builder->build('Example\Entity\Nested\Company');
        $data = [
          'id' => 11,
          'name' => '苏州蓝吧',
          'employee' => [
            'id' => 2,
            'realName' => '倪佳',
            'companyId' => 1
          ],
          'order' => [
            'id' => 1,
            'code' => '12345678',
            'orderInfo' => [
              'id' => 123,
              'desc' => 'info desc'
            ],
            'orderOthers' => [
              [
                'id' => 1, 
                'ips' => ['127.0.0.1', '127.0.0.2', '127.0.0.3'],
                'nums' => [1,2,3]
              ],
              [
                'id' => 2, 
                'ips' => ['127.0.1.1', '127.0.2.2'],
                'nums' => [1,2,3,4,5,6]
              ]
            ]
          ]
        ];
        $obj = $entity->makeInstanceWithData($data);
        \PhpRest\dump($obj);
    }

    public function test3() {
        $builder = Application::getInstance()->get(\PhpRest\Entity\EntityBuilder::class);
        $entity = $builder->build('Example\Entity\User');
        $data = [
          'id' => 11,
          'name' => '苏州蓝吧',
          'info' => '1231@qq.com'
        ];
        $obj = $entity->makeInstanceWithData($data);
        \PhpRest\dump($obj);
    }

    public function test4() {
        $builder = Application::getInstance()->get(\PhpRest\Entity\EntityBuilder::class);
        $entity = $builder->build('Example\Entity\Inherit\ObjSon');
        $data = [
          'id' => 11,
          'name' => 'jack',
          'age' => 10
        ];
        $obj = $entity->makeInstanceWithData($data);
        \PhpRest\dump($obj);
    }

    public function test5() {
      $builder = Application::getInstance()->get(\PhpRest\Entity\EntityBuilder::class);
      $entity = $builder->build('Example\Entity\Orm\User');
      \PhpRest\dump($entity);
  }
}