<?php

declare(strict_types=1);

namespace Cycle\ORM\Proxy;

use Closure;
use Cycle\ORM\EntityFactoryInterface;
use Cycle\ORM\ORMInterface;
use Cycle\ORM\RelationMap;
use Cycle\ORM\SchemaInterface;
use Doctrine\Instantiator\Instantiator;
use Laminas\Hydrator\HydratorInterface;
use Laminas\Hydrator\ReflectionHydrator;

class ProxyEntityFactory implements EntityFactoryInterface
{
    private array $classMap = [];
    private array $classScope = [];
    private Instantiator $instantiator;
    private Closure $initializer;
    private HydratorInterface $hydrator;

    public function __construct()
    {
        $this->instantiator = new Instantiator();
        $this->initializer = static function (object $self, array $properties): void {
            foreach ($properties as $name) {
                unset($self->$name);
            }
        };

        $this->hydrator = new ReflectionHydrator();
    }

    /**
     * Create empty entity
     */
    public function create(
        ORMInterface $orm,
        string $role,
        array $data
    ): object {
        $relMap = $orm->getRelationMap($role);
        $class = array_key_exists($role, $this->classMap) ? $this->classMap[$role] : $this->defineClass($orm, $relMap, $role);
        if ($class === null) {
            return (object)$data;
        }

        $proxy = $this->instantiator->instantiate($class);
        $proxy->__cycle_orm_rel_map = $relMap;
        foreach ($this->classScope[$role] as $scope => $properties) {
            Closure::bind($this->initializer, null, $scope)($proxy, $properties);
        }

        return $proxy;
    }

    public function upgrade(
        ORMInterface $orm,
        string $role,
        object $entity,
        array $data
    ): object {
        // new set of data and relations always overwrite entity state
        return $this->hydrator->hydrate($data, $entity);
    }

    public function extractRelations(RelationMap $relMap, object $entity): array
    {
        if (!property_exists($entity, '__cycle_orm_rel_data')) {
            return array_intersect_key($this->entityToArray($entity), $relMap->getRelations());
        }
        $currentData = $entity->__cycle_orm_rel_data;
        foreach ($relMap->getRelations() as $key => $relation) {
            if (!array_key_exists($key, $currentData)) {
                $arrayData ??= $this->entityToArray($entity);
                $currentData[$key] = $arrayData[$key];
            }
        }
        return $currentData;
    }

    public function extractData(RelationMap $relMap, object $entity): array
    {
        return array_diff_key($this->entityToArray($entity), $relMap->getRelations());
    }

    private function entityToArray(object $entity): array
    {
        $result = [];
        foreach ((array)$entity as $key => $value) {
            $result[$key[0] === "\0" ? substr($key, strrpos($key, "\0", 1) + 1) : $key] = $value;
        }
        return $result;
    }

    private function defineClass(OrmInterface $orm, RelationMap $relMap, string $role): ?string
    {
        // $mapper->geteEntityMap or getClass // todo morphed
        $class = $orm->getSchema()->define($role, SchemaInterface::ENTITY);
        if (!class_exists($class, true)) {
            $this->classMap[$role] = null;
            $this->classScope[$role] = $this->getScope(null, $relMap);
            return null;
        }
        if (array_key_exists($class, $this->classMap)) {
            $this->classMap[$role] = $this->classMap[$class];
            $this->classScope[$role] = $this->classScope[$class];
            return $this->classMap[$class];
        }
        $reflection = new \ReflectionClass($class);
        if ($reflection->isFinal()) {
            throw new \RuntimeException('Entity class can\'t be extended.');
        }
        $className = "{$class} Cycle ORM Proxy";
        // $className = "PROXY_" . md5($class);
        $this->classMap[$role] = $className;
        $this->classMap[$class] = $className;
        $this->classScope[$role] = $this->getScope($class, $relMap);
        // Todo Interface
        if (!class_exists($className, false)) {
            if (strpos($className, '\\') !== false) {
                $pos = strrpos($className, '\\');
                $namespaceStr = sprintf("namespace %s;\n", substr($className, 0, $pos));
                $classNameStr = substr($className, $pos + 1);
            } else {
                $namespaceStr = '';
                $classNameStr = $className;
            }

            /** @see \Cycle\ORM\Proxy\EntityProxyTrait */
            $classStr = <<<PHP
                {$namespaceStr}
                class {$classNameStr} extends \\{$class} {
                    use \\Cycle\ORM\\Proxy\\EntityProxyTrait;
                }
                PHP;
            eval($classStr);
        }

        return $className;
    }

    private function getScope(?string $class, RelationMap $relMap): array
    {
        if ($class === null) {
            return array_keys($relMap->getRelations());
        }
        // todo reflection
        return [$class => array_keys($relMap->getRelations())];
    }
}
