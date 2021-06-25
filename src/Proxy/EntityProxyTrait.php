<?php

declare(strict_types=1);

namespace Cycle\ORM\Proxy;

use Cycle\ORM\Promise\Deferred;
use Cycle\ORM\RelationMap;

trait EntityProxyTrait
{
    public RelationMap $__cycle_orm_rel_map;
    public array $__cycle_orm_rel_data = [];

    public function __get(string $name)
    {
        if (!array_key_exists($name, $this->__cycle_orm_rel_map->getRelations())) {
            return $this->$name;
        }
        $value = $this->__cycle_orm_rel_data[$name] ?? null;
        if ($value instanceof Deferred) {
            return $this->$name = $value->getData();
        }
        throw new \RuntimeException(sprintf('Property %s.%s is not initialized.', parent::class, $name));
    }

    public function __set($name, $value)
    {
        if (!array_key_exists($name, $this->__cycle_orm_rel_map->getRelations())) {
            throw new \RuntimeException("Property {$name} is protected.");
        }
        if ($value instanceof Deferred) {
            $this->__cycle_orm_rel_data[$name] = $value;
            return;
        }
        $this->$name = $value;
    }
}
