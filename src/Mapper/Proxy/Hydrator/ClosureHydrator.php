<?php

declare(strict_types=1);

namespace Cycle\ORM\Mapper\Proxy\Hydrator;

use Cycle\ORM\EntityProxyInterface;
use Cycle\ORM\Exception\MapperException;
use Cycle\ORM\Reference\ReferenceInterface;
use Cycle\ORM\RelationMap;

/**
 * @internal
 */
class ClosureHydrator
{
    /**
     * @param array<string, PropertyMap> $propertyMaps Array of class properties
     */
    public function hydrate(RelationMap $relMap, array $propertyMaps, object $object, array $data): object
    {
        $isProxy = $object instanceof EntityProxyInterface;

        $properties = $propertyMaps[ClassPropertiesExtractor::KEY_FIELDS]->getProperties();
        $this->setEntityProperties($properties, $object, $data);

        if (!$isProxy) {
            $properties = $propertyMaps[ClassPropertiesExtractor::KEY_RELATIONS]->getProperties();
            if ($properties !== []) {
                $this->setRelationProperties($properties, $relMap, $object, $data);
            }
        }

        foreach ($data as $property => $value) {
            try {
                if (isset($relMap->getRelations()[$property])) {
                    unset($object->{$property});
                }
                // Use @ to try to ignore deprecations
                @$object->{$property} = $value;
            } catch (\Throwable $e) {
                if ($e::class === \TypeError::class) {
                    throw new MapperException(
                        "Can't hydrate an entity because property and value types are incompatible.",
                        previous: $e,
                    );
                }
            }
        }

        return $object;
    }

    /**
     * Map private entity properties
     */
    private function setEntityProperties(array $properties, object $object, array &$data): void
    {
        foreach ($properties as $class => $props) {
            if ($class === '') {
                continue;
            }

            \Closure::bind(static function (object $object, array $props, array &$data): void {
                foreach ($props as $property) {
                    if (!\array_key_exists($property, $data)) {
                        continue;
                    }

                    try {
                        // Use @ to try to ignore deprecations
                        @$object->{$property} = $data[$property];
                        unset($data[$property]);
                    } catch (\Throwable $e) {
                        if ($e::class === \TypeError::class) {
                            throw new MapperException(
                                "Can't hydrate an entity because property and value types are incompatible.",
                                previous: $e,
                            );
                        }
                    }
                }
            }, null, $class)($object, $props, $data);
        }
    }

    /**
     * Map private relations of non-proxy entity
     */
    private function setRelationProperties(array $properties, RelationMap $relMap, object $object, array &$data): void
    {
        $refl = new \ReflectionClass($object);
        $setter = static function (object $object, array $props, array &$data) use ($refl, $relMap): void {
            foreach ($props as $property) {
                if (!\array_key_exists($property, $data)) {
                    continue;
                }

                $value = $data[$property];

                if ($value instanceof ReferenceInterface) {
                    $prop = $refl->getProperty($property);

                    if ($prop->hasType()) {
                        // todo: we can cache this
                        /** @var \ReflectionNamedType[] $types */
                        $types = $prop->getType() instanceof \ReflectionUnionType
                            ? $prop->getType()->getTypes()
                            : [$prop->getType()];

                        $relation = $relMap->getRelations()[$property];
                        foreach ($types as $type) {
                            $c = $type->getName();
                            if ($c === 'object' || $c === 'mixed' || $value instanceof $c) {
                                $value = $relation->collect($relation->resolve($value, false)) ?? $value;

                                $object->{$property} = $value;
                                unset($data[$property]);

                                // go to next property
                                continue 2;
                            }
                        }
                        $value = $relation->collect($relation->resolve($value, true));
                    }
                }

                $object->{$property} = $value;
                unset($data[$property]);
            }
        };

        foreach ($properties as $class => $props) {
            if ($class === '') {
                // Hydrate public properties
                $setter($object, $props, $data);
                continue;
            }

            \Closure::bind($setter, null, $class)($object, $props, $data);
        }
    }
}
