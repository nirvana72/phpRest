<?php
namespace PhpRest\Orm;

use PhpRest\Application;
use PhpRest\Entity\EntityBuilder;
use PhpRest\Exception\BadCurdException;

trait EnableOrm
{
    /**
     * 填充数据
     * 
     * @param array $data 数据源
     * @param bool $withValidator 是否需要验证
     */
    public function fill(array $data, bool $withValidator = true)
    {
        $entity = Application::getInstance()->get(EntityBuilder::class)->build(self::class);
        return $entity->makeInstanceWithData($data, $withValidator, $this);
    }

    public static function findOne($where = [])
    {
        $self   = Application::getInstance()->make(self::class);
        $entity = Application::getInstance()->get(EntityBuilder::class)->build(self::class);
        $columns = [];
        foreach ($entity->properties as $property) {
            $columns[] = "{$property->field}({$property->name})";
        }
        $data = $self->getDb()->get($entity->table, $columns, $where);
        if ($data === null) return null;
        $entity->makeInstanceWithData($data, false, $self);
        return $self;
    }

    public function insert()
    {
        $entity = Application::getInstance()->get(EntityBuilder::class)->build(self::class);
        $data = [];
        $pkProperty = null;
        foreach ($entity->properties as $property) {
            if ($property->isPrimaryKey && $property->isAutoIncrement) {
                $pkProperty = $property->name;
            }
            if ($property->isAutoIncrement) {
                continue;
            }
            $data[$property->field] = $this->{$property->name};
        }
        $res = $this->getDb()->insert($entity->table, $data);
        if ($res->errorInfo()[1] !== null) {
            throw new BadCurdException($res->errorInfo()[2]);
        }
        $autoId = $this->getDb()->id();
        if ($autoId !== null && $pkProperty !== null) {
            $this->{$pkProperty} = $autoId;
        }
        return $res;
    }

    public function update() 
    {
        $entity = Application::getInstance()->get(EntityBuilder::class)->build(self::class);
        $data = [];
        $where = [];
        foreach ($entity->properties as $property) {
            if ($property->isPrimaryKey) {
                $where[$property->field] = $this->{$property->name};
            } elseif (isset($this->{$property->name})) {
                $data[$property->field] = $this->{$property->name};
            }
        }
        $res = $this->getDb()->update($entity->table, $data, $where);
        if ($res->errorInfo()[1] !== null) {
            throw new BadCurdException($res->errorInfo()[2]);
        }
        return $res;
    }

    public static function delete($pk = null) 
    {
        $self = Application::getInstance()->make(self::class);
        return $self->remove($pk);
    }

    public function remove($pk = null) 
    {
        $entity = Application::getInstance()->get(EntityBuilder::class)->build(self::class);
        $where = $pk;
        if ($where === null) {
            foreach ($entity->properties as $property) {
                if ($property->isPrimaryKey) {
                    $where[$property->field] = $this->{$property->name};
                }
            }
        }
        $res = $this->getDb()->delete($entity->table, $where);
        if ($res->errorInfo()[1] !== null) {
            throw new BadCurdException($res->errorInfo()[2]);
        }
        return $res;
    }
    
    private function getDb() 
    {
        return Application::getInstance()->get(\Medoo\Medoo::class);
    }
}