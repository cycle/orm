<?php

declare(strict_types=1);

namespace Cycle\ORM\Mapper\Proxy;

use Cycle\ORM\Mapper\Proxy\Hydrator\ClassPropertiesExtractor;
use Cycle\ORM\Mapper\Proxy\Hydrator\ClosureHydrator;
use Cycle\ORM\Mapper\Proxy\Hydrator\PropertyMap;
use Cycle\ORM\RelationMap;
use Doctrine\Instantiator\Instantiator;

/**
 * @internal
 */
class ProxyEntityFactory
{
    /**
     * @var string[]
     *
     * @psalm-var class-string[]
     */
    private array $classMap = [];

    /** @var PropertyMap[] */
    private array $classProperties = [];

    private Instantiator $instantiator;
    private \Closure $initializer;

    public function __construct(
        private ClosureHydrator $hydrator,
        private ClassPropertiesExtractor $propertiesExtractor,
    ) {
        $this->instantiator = new Instantiator();
        $this->initializer = static function (object $entity, array $properties): void {
            foreach ($properties as $name) {
                unset($entity->$name);
            }
        };
    }

    /**
     * Creates an empty Entity.
     */
    public function create(
        RelationMap $relMap,
        string $sourceClass,
    ): object {
        $class = \array_key_exists($sourceClass, $this->classMap)
            ? $this->classMap[$sourceClass]
            : $this->defineClass($sourceClass);

        $proxy = $this->instantiator->instantiate($class);
        $proxy->__cycle_orm_rel_map = $relMap;
        $scopes = $this->getEntityProperties($proxy, $relMap);
        $proxy->__cycle_orm_relation_props = $scopes[ClassPropertiesExtractor::KEY_RELATIONS];

        // init
        foreach ($scopes[ClassPropertiesExtractor::KEY_RELATIONS]->getProperties() as $scope => $properties) {
            \Closure::bind($this->initializer, null, $scope === '' ? $class : $scope)($proxy, $properties);
        }

        return $proxy;
    }

    /**
     * Sets an Entity's column values from data.
     *
     * @return object Entity with hydrated data
     */
    public function upgrade(
        RelationMap $relMap,
        object $entity,
        array $data,
    ): object {
        $properties = $this->getEntityProperties($entity, $relMap);

        // new set of data and relations always overwrite entity state
        return $this->hydrator->hydrate(
            $relMap,
            $properties,
            $entity,
            $data,
        );
    }

    /**
     * Get an entity relation column values as array, except primitive columns.
     *
     * @return array array<string, mixed>
     */
    public function extractRelations(RelationMap $relMap, object $entity): array
    {
        if (!\property_exists($entity, '__cycle_orm_rel_data')) {
            return \array_intersect_key($this->entityToArray($entity), $relMap->getRelations());
        }

        $currentData = $entity->__cycle_orm_rel_data;
        foreach ($relMap->getRelations() as $key => $relation) {
            if (!\array_key_exists($key, $currentData)) {
                $arrayData ??= $this->entityToArray($entity);

                if (\array_key_exists($key, $arrayData)) {
                    $currentData[$key] = $arrayData[$key];
                }
            }
        }

        return $currentData;
    }

    /**
     * Get an entity column values as array, except relations.
     *
     * @return array<string, mixed>
     */
    public function extractData(RelationMap $relMap, object $entity): array
    {
        return \array_diff_key($this->entityToArray($entity), $relMap->getRelations());
    }

    private function entityToArray(object $entity): array
    {
        $result = [];
        foreach ((array) $entity as $key => $value) {
            $result[$key[0] === "\0" ? \substr($key, \strrpos($key, "\0", 1) + 1) : $key] = $value;
        }

        unset(
            $result['__cycle_orm_rel_map'],
            $result['__cycle_orm_rel_data'],
            $result['__cycle_orm_relation_props'],
        );

        return $result;
    }

    private function defineClass(string $class): string
    {
        if (!\class_exists($class, true)) {
            throw new \RuntimeException(\sprintf(
                'The entity `%s` class does not exist. Proxy factory can not create classless entities.',
                $class,
            ));
        }

        if (\array_key_exists($class, $this->classMap)) {
            return $this->classMap[$class];
        }

        $reflection = new \ReflectionClass($class);
        if ($reflection->isFinal()) {
            throw new \RuntimeException(\sprintf('The entity `%s` class is final and can\'t be extended.', $class));
        }
        $className = "{$class} Cycle ORM Proxy";
        $this->classMap[$class] = $className;

        if (!\class_exists($className, false)) {
            if (\str_contains($className, '\\')) {
                $pos = \strrpos($className, '\\');
                $namespaceStr = \sprintf("namespace %s;\n", \substr($className, 0, $pos));
                $classNameStr = \substr($className, $pos + 1);
            } else {
                $namespaceStr = '';
                $classNameStr = $className;
            }

            /** @see \Cycle\ORM\Mapper\Proxy\EntityProxyTrait */
            $classStr = <<<PHP
                {$namespaceStr}
                #[\AllowDynamicProperties]
                class {$classNameStr} extends \\{$class} implements \\Cycle\\ORM\\EntityProxyInterface {
                    use \\Cycle\ORM\\Mapper\\Proxy\\EntityProxyTrait;
                }
                PHP;

            eval($classStr);
        }

        return $className;
    }

    /**
     * Gets property map (primitive fields, relations) for given Entity.
     *
     * @return PropertyMap[]
     * @throws \ReflectionException
     */
    private function getEntityProperties(object $entity, RelationMap $relMap): array
    {
        return $this->classProperties[$entity::class] ??= $this->propertiesExtractor
            ->extract($entity, \array_keys($relMap->getRelations()));
    }
}
