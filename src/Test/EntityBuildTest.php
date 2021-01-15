<?php
namespace PhpRest\Test;

class EntityBuildTest
{
    public function test1($app) {
        $builder = $app->get(\PhpRest\Entity\EntityBuilder::class);
        $entity = $builder->build('Example\Entity\Company');
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
            'info' => [
              'id' => 123,
              'desc' => 'info desc'
            ]
          ]
        ];
        $obj = $entity->makeInstanceWithData($app, $data);
        \PhpRest\dump($obj);
    }
}