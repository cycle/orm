<?php

declare(strict_types=1);

namespace Cycle\ORM\Mapper\Proxy;

use Cycle\ORM\RelationMap;

/**
 * @internal
 */
class ClasslessProxyFactory
{
    /**
     * @var string[]
     *
     * @psalm-var class-string
     */
    private array $classMap = [];

    /**
     * Create empty entity
     *
     * @param non-empty-string $role
     * @param array<non-empty-string, mixed> $data
     */
    public function create(
        RelationMap $relMap,
        string $role,
        array $data,
    ): object {
        $class = $this->defineClass($role, $relMap, $data);
        $proxy = new $class();
        $proxy->__cycle_orm_rel_map = $relMap;
        return $proxy;
    }

    public function upgrade(
        object $entity,
        array $data,
    ): object {
        foreach ($data as $key => $value) {
            $entity->$key = $value;
        }
        return $entity;
    }

    public function extractRelations(RelationMap $relMap, object $entity): array
    {
        if (!\property_exists($entity, '__cycle_orm_rel_data')) {
            return \array_intersect_key($this->entityToArray($entity), $relMap->getRelations());
        }
        $currentData = $entity->__cycle_orm_rel_data;
        foreach ($relMap->getRelations() as $key => $relation) {
            if (!\array_key_exists($key, $currentData)) {
                $arrayData ??= $this->entityToArray($entity);
                $currentData[$key] = $arrayData[$key];
            }
        }
        return $currentData;
    }

    public function extractData(RelationMap $relMap, object $entity): array
    {
        return \array_diff_key($this->entityToArray($entity), $relMap->getRelations());
    }

    public function entityToArray(object $entity): array
    {
        $result = [];
        foreach ((array) $entity as $key => $value) {
            $result[$key[0] === "\0" ? \substr($key, \strrpos($key, "\0", 1) + 1) : $key] = $value;
        }
        $relations = $result['__cycle_orm_rel_data'];
        unset($result['__cycle_orm_rel_map'], $result['__cycle_orm_rel_data']);
        return $relations + $result;
    }

    /**
     * @param non-empty-string $role
     * @param array<non-empty-string, mixed> $fields
     *
     * @return class-string
     */
    private function defineClass(string $role, RelationMap $relMap, array $fields): string
    {
        if (\array_key_exists($role, $this->classMap)) {
            return $this->classMap[$role];
        }
        $i = 0;
        do {
            $className = "Classless {$role} {$i} Cycle ORM Proxy";
            $namespace = 'Cycle\\ORM\\ClasslessProxy';
            $class = $namespace . '\\' . $className;
            ++$i;
        } while (\class_exists($class, false));

        $properties = [];
        // Generate properties
        foreach ($fields as $field) {
            $properties[] = "public \${$field};";
        }
        foreach ($relMap->getRelations() as $field => $relation) {
            $properties[] = "private \${$field};";
        }
        $properties = \implode("\n    ", $properties);

        $this->classMap[$role] = $class;
        /** @see \Cycle\ORM\Mapper\Proxy\ClasslessProxyTrait */
        $classStr = <<<PHP
            namespace {$namespace};
            class {$className} implements \\Cycle\\ORM\\EntityProxyInterface {
                use \\Cycle\ORM\\Mapper\\Proxy\\ClasslessProxyTrait;

                {$properties}
            }
            PHP;
        eval($classStr);
        return $class;
    }
}
