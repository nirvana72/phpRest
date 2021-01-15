<?php
namespace PhpRest\Test;

class EntityBuildTest
{
    public function test1() {
        $builder = new \PhpRest\Entity\EntityBuilder();
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
            'code' => '12345678'
          ]
        ];
        $obj = $entity->makeInstanceWithData($data);
        \PhpRest\dump($obj);
    }
}