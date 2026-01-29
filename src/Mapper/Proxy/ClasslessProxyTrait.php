<?php

declare(strict_types=1);

namespace Cycle\ORM\Mapper\Proxy;

use Cycle\ORM\Reference\ReferenceInterface;
use Cycle\ORM\RelationMap;

/**
 * @internal
 */
trait ClasslessProxyTrait
{
    public RelationMap $__cycle_orm_rel_map;
    public array $__cycle_orm_rel_data = [];

    public function __get(string $name)
    {
        $relation = $this->__cycle_orm_rel_map->getRelations()[$name] ?? null;
        if ($relation === null) {
            throw new \RuntimeException(\sprintf('Undefined property %s.%s.', static::class, $name));
        }
        $value = $this->__cycle_orm_rel_data[$name] ?? null;
        if ($value instanceof ReferenceInterface) {
            $this->$name = $relation->collect($relation->resolve($value, true));
            unset($this->__cycle_orm_rel_data[$name]);
            return $this->$name;
        }
        return $value ?? $this->$name;
    }

    public function __set(string $name, mixed $value): void
    {
        if (!\array_key_exists($name, $this->__cycle_orm_rel_map->getRelations())) {
            $this->$name = $value;
            return;
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
        $result = (array) $this;
        unset($result['__cycle_orm_rel_map'], $result['__cycle_orm_rel_data'], $result['__cycle_orm_relation_props']);
        return $result;
    }
}
