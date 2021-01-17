<?php
namespace PhpRest\Orm;

use PhpRest\Entity\EntityBuilder;

trait EnableOrm
{
    /**
     * @Inject
     * @var \PhpRest\Application
     */
    private $app;

    /**
     * @Inject
     * @var \Medoo\Medoo
     */
    private $db;

    private function getEntity() 
    {
        return $this->app->get(EntityBuilder::class)->build(self::class);
    }

    /**
     * 填充数据
     * 
     * @param array $data 数据源
     * @param bool  $withValidator 是否需要验证
     */
    public function fill($data, $withValidator = true)
    {
        $entity = $this->getEntity();
        return $entity->makeInstanceWithData($this->app, $data, $withValidator, $this);
    }

    public function findOne($where = [])
    {
        $entity = $this->getEntity();
        $columns = [];
        foreach ($entity->properties as $property) {
            $columns[$property->field] = $this->{$property->name};
        }
        $data = $this->db->get($entity->table, $columns, $where);
        $data = \PhpRest\camelizeArrayKey($data);
        $entity->makeInstanceWithData($this->app, $data, false, $this);
    }

    public function insert()
    {
        $entity = $this->getEntity();
        $data = [];
        foreach ($entity->properties as $property) {
            if ($property->isAutoIncrement) {
                continue;
            }
            $data[$property->field] = $this->{$property->name};
        }
        return $this->db->insert($entity->table, $data);
    }

    public function update() 
    {
        $entity = $this->getEntity();
        $data = [];
        $where = [];
        foreach ($entity->properties as $property) {
            if ($property->isPrimaryKey) {
                $where[$property->field] = $this->{$property->name};
            } elseif (isset($this->{$property->name})) {
                $data[$property->field] = $this->{$property->name};
            }
        }
        return $this->db->update($entity->table, $data, $where);
    }

    public function delete($pk = null) 
    {
        $entity = $this->getEntity();
        $where = $pk;
        if ($where === null) {
            foreach ($entity->properties as $property) {
                if ($property->isPrimaryKey) {
                    $where[$property->field] = $this->{$property->name};
                }
            }
        }
        
        return $this->db->delete($entity->table, $where);
    }
}