<?php

declare(strict_types=1);

namespace Cycle\ORM\Proxy;

use Closure;
use Cycle\ORM\EntityFactoryInterface;
use Cycle\ORM\ORMInterface;
use Cycle\ORM\RelationMap;
use Cycle\ORM\SchemaInterface;
use Doctrine\Instantiator\Instantiator;

class EntityProxyFactory implements EntityFactoryInterface
{
    private array $classMap = [];
    private array $classScope = [];
    private Instantiator $instantiator;
    private Closure $initializer;

    public function __construct()
    {
        $this->instantiator = new Instantiator();
        $this->initializer = static function (object $self, array $properties): void {
            foreach ($properties as $name) {
                unset($self->$name);
            }
        };
    }

    /**
     * Create empty entity
     */
    public function create(
        ORMInterface $orm,
        string $role,
        RelationMap $relMap,
        array $data
    ): object {
        $mapper = $orm->getMapper($role);

        // foreach ($relMap->getRelations() as $relName => $relation) {
        //     if (array_key_exists($relName, $data)) {
        //         continue;
        //     }
        //     $data[$relName] = $relation->initDeferred();
        // }

        return $this->makeInstance($orm, $relMap, $role, $data);
        return $mapper->init($data)[0];

        // hydrate entity with it's data, relations and proxies
        // return $mapper->hydrate($entity, $data);
    }

    public function upgrade(
        ORMInterface $orm,
        string $role,
        object $entity,
        array $data
    ): object {
        $mapper = $orm->getMapper($role);

        // new set of data and relations always overwrite entity state
        return $mapper->hydrate($entity, $data);
    }

    public function defineClass(OrmInterface $orm, RelationMap $relMap, string $role): ?string
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

    private function makeInstance(ORMInterface $orm,RelationMap $relMap, string $role, array $data): object
    {
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

    private function getScope(?string $class, RelationMap $relMap): array
    {
        if ($class === null) {
            return array_keys($relMap->getRelations());
        }
        // todo reflection
        return [$class => array_keys($relMap->getRelations())];
    }

}
