<?php

declare(strict_types=1);

namespace Cycle\ORM\Proxy;

use Cycle\ORM\Promise\ReferenceInterface;
use Cycle\ORM\RelationMap;

trait EntityProxyTrait
{
    public RelationMap $__cycle_orm_rel_map;
    public array $__cycle_orm_rel_data = [];

    public function __get(string $name)
    {
        $relations = $this->__cycle_orm_rel_map->getRelations();
        if (!array_key_exists($name, $relations)) {
            return $this->$name;
        }
        $value = $this->__cycle_orm_rel_data[$name] ?? null;
        if ($value instanceof ReferenceInterface) {
            $this->$name = $relations[$name]->resolve($value, true);
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
}
