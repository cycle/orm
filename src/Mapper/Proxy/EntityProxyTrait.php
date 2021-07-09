<?php

declare(strict_types=1);

namespace Cycle\ORM\Mapper\Proxy;

use Cycle\ORM\Reference\ReferenceInterface;
use Cycle\ORM\RelationMap;

trait EntityProxyTrait
{
    public RelationMap $__cycle_orm_rel_map;
    public array $__cycle_orm_rel_data = [];

    public function __clone()
    {
        throw new \Exception('You should not clone the Proxy.');
    }

    public function __get(string $name)
    {
        $relation = $this->__cycle_orm_rel_map->getRelations()[$name] ?? null;
        if ($relation === null) {
            return $this->$name;
        }
        $value = $this->__cycle_orm_rel_data[$name] ?? null;
        if ($value instanceof ReferenceInterface) {
            $this->$name = $relation->collect($relation->resolve($value, true));
            unset($this->__cycle_orm_rel_data[$name]);
            return $this->$name;
        }
        throw new \RuntimeException(sprintf('Property %s.%s is not initialized.', get_parent_class(static::class), $name));
    }

    public function __set(string $name, $value)
    {
        if (!array_key_exists($name, $this->__cycle_orm_rel_map->getRelations())) {
            throw new \RuntimeException("Property {$name} is protected.");
        }
        if ($value instanceof ReferenceInterface) {
            $this->__cycle_orm_rel_data[$name] = $value;
            return;
        }
        unset($this->__cycle_orm_rel_data[$name]);
        $this->$name = $value;
    }

    public function __debugInfo(): array
    {
        $result = (array)$this;
        unset($result['__cycle_orm_rel_map'], $result['__cycle_orm_rel_data']);
        return $result;
    }
}
