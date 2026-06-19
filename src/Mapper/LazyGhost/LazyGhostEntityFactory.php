<?php

declare(strict_types=1);

namespace Cycle\ORM\Mapper\LazyGhost;

use Cycle\ORM\Reference\ReferenceInterface;
use Cycle\ORM\Relation\ActiveRelationInterface;
use Cycle\ORM\RelationMap;

/**
 * Entity factory using PHP 8.4 lazy ghost objects.
 *
 * Alternative to {@see \Cycle\ORM\Mapper\Proxy\ProxyEntityFactory} that solves
 * PHP 8.4 compatibility issues:
 * - private(set) properties (isPublic() returns true, hydrator skips Closure::bind)
 * - final classes (proxy cannot extend)
 * - (array) cast (mangles private(set) property names, skips virtual properties)
 *
 * Uses ReflectionClass::newLazyGhost() for constructor-less instantiation,
 * ReflectionProperty::setRawValueWithoutLazyInitialization() for hydration,
 * and WeakMap for tracking pending relation references.
 *
 * @internal
 */
class LazyGhostEntityFactory
{
    /**
     * Pending relation references per entity.
     *
     * @var \WeakMap<object, array<string, array{ref: ReferenceInterface, relation: ActiveRelationInterface}>>
     */
    protected \WeakMap $pendingRefs;

    /** @var array<class-string, \ReflectionClass> */
    protected array $reflectionCache = [];

    /**
     * Cached property lookups: false means "no usable property".
     *
     * @var array<class-string, array<string, \ReflectionProperty|false>>
     */
    protected array $propertyCache = [];

    /**
     * Cached list of extractable (non-static, non-virtual) properties per class.
     *
     * @var array<class-string, list<\ReflectionProperty>>
     */
    protected array $extractableProperties = [];

    public function __construct()
    {
        $this->pendingRefs = new \WeakMap();
    }

    /**
     * Create an empty entity instance (without calling its constructor).
     *
     * The returned ghost object resolves pending relation references
     * on first access to an uninitialized property.
     */
    public function create(RelationMap $relMap, string $sourceClass): object
    {
        $reflection = $this->getReflection($sourceClass);
        $ghost = $reflection->newLazyGhost($this->createInitializer($sourceClass));
        $this->pendingRefs[$ghost] = [];

        return $ghost;
    }

    /**
     * Hydrate an entity with column values and relation data.
     *
     * Two-phase approach is critical for BelongsTo relations:
     * Cycle ORM passes both inner key (e.g. `role_id`) and the relation
     * (e.g. `role` as ReferenceInterface) in $data. If scalar properties
     * are set first, a missing ReflectionProperty for `role_id` triggers
     * the fallback path (`@$entity->$prop = $value`), which accesses the
     * ghost and fires the initializer BEFORE the relation ref is registered
     * in pendingRefs — leaving the relation property uninitialized forever.
     *
     * Phase 1: Register ALL relation references in pendingRefs.
     * Phase 2: Set column/scalar properties (safe to trigger initializer now).
     *
     * On already-initialized entities (re-hydration), relation references
     * are resolved immediately via ReflectionProperty::setValue().
     *
     * @return object Hydrated entity
     */
    public function upgrade(RelationMap $relMap, object $entity, array $data): object
    {
        $reflection = $this->getReflection($entity::class);
        $relations = $relMap->getRelations();
        $hasPendingRefs = false;
        $isLazyUninitialized = $reflection->isUninitializedLazyObject($entity);

        // Phase 1: Register pending relation references BEFORE touching any properties.
        // This ensures the ghost initializer has all refs available if triggered
        // by a fallback property write (e.g. role_id triggering ghost init).
        foreach ($data as $property => $value) {
            $relation = $relations[$property] ?? null;

            if ($relation === null || !$value instanceof ReferenceInterface) {
                continue;
            }

            if ($isLazyUninitialized) {
                $pending = $this->pendingRefs[$entity] ?? [];
                $pending[$property] = [
                    'ref' => $value,
                    'relation' => $relation,
                ];
                $this->pendingRefs[$entity] = $pending;
                $hasPendingRefs = true;
            } else {
                // Re-hydration on already initialized entity — resolve immediately
                $resolved = $relation->collect($relation->resolve($value, true));
                $prop = $this->getProperty($reflection, $property);

                if ($prop !== null && !($prop->isReadOnly() && $prop->isInitialized($entity))) {
                    $prop->setValue($entity, $resolved);
                }
            }
        }

        // Phase 2: Set column/scalar properties
        foreach ($data as $property => $value) {
            if (isset($relations[$property])) {
                continue;
            }

            $prop = $this->getProperty($reflection, $property);

            if ($prop !== null) {
                if ($prop->isReadOnly() && $prop->isInitialized($entity)) {
                    continue;
                }

                $prop->setRawValueWithoutLazyInitialization($entity, $value);
            } else {
                // Dynamic property or virtual — fallback (matches ClosureHydrator behavior)
                try {
                    @$entity->{$property} = $value;
                } catch (\Throwable) {
                }
            }
        }

        // If ghost is still uninitialized and no pending refs, mark as initialized
        if ($isLazyUninitialized && !$hasPendingRefs) {
            $reflection->markLazyObjectAsInitialized($entity);
        }

        return $entity;
    }

    /**
     * Extract all non-relation property values.
     *
     * @return array<string, mixed>
     */
    public function extractData(RelationMap $relMap, object $entity): array
    {
        $relations = $relMap->getRelations();
        $result = [];

        foreach ($this->getExtractableProperties($entity::class) as $prop) {
            $name = $prop->getName();

            if (isset($relations[$name])) {
                continue;
            }

            if (!$prop->isInitialized($entity)) {
                continue;
            }

            $result[$name] = $prop->getValue($entity);
        }

        return $result;
    }

    /**
     * Extract relation values.
     *
     * For pending (unresolved) references, returns the ReferenceInterface
     * without triggering resolution (preserves laziness for ORM internals).
     *
     * @return array<string, mixed>
     */
    public function extractRelations(RelationMap $relMap, object $entity): array
    {
        $result = [];
        $pending = $this->pendingRefs[$entity] ?? [];
        $reflection = $this->getReflection($entity::class);

        foreach (\array_keys($relMap->getRelations()) as $name) {
            if (isset($pending[$name])) {
                $result[$name] = $pending[$name]['ref'];
                continue;
            }

            $prop = $this->getProperty($reflection, $name);

            if ($prop !== null && $prop->isInitialized($entity)) {
                $result[$name] = $prop->getValue($entity);
            }
        }

        return $result;
    }

    /**
     * @return array<string, mixed>
     */
    public function extractAll(RelationMap $relMap, object $entity): array
    {
        return $this->extractData($relMap, $entity) + $this->extractRelations($relMap, $entity);
    }

    /**
     * Create the ghost initializer closure for a given class.
     *
     * When any uninitialized property is accessed, the initializer:
     * 1. Resolves ALL pending relation references for the entity
     * 2. Sets resolved values via setRawValueWithoutLazyInitialization()
     * 3. Clears pending refs (WeakMap entry)
     */
    protected function createInitializer(string $class): \Closure
    {
        return function (object $entity) use ($class): void {
            $refs = $this->pendingRefs[$entity] ?? [];
            $reflection = $this->getReflection($class);

            foreach ($refs as $name => $info) {
                $resolved = $info['relation']->collect(
                    $info['relation']->resolve($info['ref'], true),
                );

                $prop = $this->getProperty($reflection, $name);
                $prop?->setRawValueWithoutLazyInitialization($entity, $resolved);
            }

            unset($this->pendingRefs[$entity]);
        };
    }

    protected function getReflection(string $class): \ReflectionClass
    {
        return $this->reflectionCache[$class] ??= new \ReflectionClass($class);
    }

    /**
     * Get a usable ReflectionProperty for hydration/extraction.
     *
     * Returns null for non-existent, static, or virtual (hook-only) properties.
     * Results are cached per class+property name.
     */
    protected function getProperty(\ReflectionClass $reflection, string $name): ?\ReflectionProperty
    {
        $className = $reflection->getName();

        if (!\array_key_exists($name, $this->propertyCache[$className] ?? [])) {
            $this->propertyCache[$className][$name] = $this->resolveProperty($reflection, $name);
        }

        $cached = $this->propertyCache[$className][$name];

        return $cached === false ? null : $cached;
    }

    /**
     * Get cached list of extractable properties (non-static, non-virtual).
     *
     * @return list<\ReflectionProperty>
     */
    protected function getExtractableProperties(string $class): array
    {
        if (!isset($this->extractableProperties[$class])) {
            $reflection = $this->getReflection($class);
            $properties = [];

            foreach ($reflection->getProperties() as $prop) {
                if ($prop->isStatic() || $prop->isVirtual()) {
                    continue;
                }

                $properties[] = $prop;
            }

            $this->extractableProperties[$class] = $properties;
        }

        return $this->extractableProperties[$class];
    }

    protected function resolveProperty(\ReflectionClass $reflection, string $name): \ReflectionProperty|false
    {
        if (!$reflection->hasProperty($name)) {
            return false;
        }

        $prop = $reflection->getProperty($name);

        if ($prop->isStatic() || $prop->isVirtual()) {
            return false;
        }

        return $prop;
    }
}
