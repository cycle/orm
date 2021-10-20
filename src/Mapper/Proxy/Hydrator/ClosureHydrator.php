<?php

declare(strict_types=1);

namespace Cycle\ORM\Mapper\Proxy\Hydrator;

use Closure;
use Cycle\ORM\Reference\ReferenceInterface;
use Cycle\ORM\RelationMap;

class ClosureHydrator
{
    /**
     * @param array<string, PropertyMap> $propertyMaps Array of class properties
     */
    public function hydrate(RelationMap $relMap, array $propertyMaps, object $object, array $data): object
    {
        $isProxy = str_ends_with(get_class($object), 'Proxy');

        $properties = $propertyMaps[ClassPropertiesExtractor::KEY_FIELDS]->getProperties();
        $this->setEntityProperties($properties, $object, $data);

        if (!$isProxy) {
            $properties = $propertyMaps[ClassPropertiesExtractor::KEY_RELATIONS]->getProperties();
            $this->setRelationProperties($properties, $relMap, $object, $data);
        }

        foreach ($data as $property => $value) {
            $object->{$property} = $value;
        }

        return $object;
    }

    private function setEntityProperties(array $properties, object $object, array &$data): void
    {
        foreach ($properties as $class => $props) {
            if ($class === '') {
                continue;
            }

            Closure::bind(static function (object $object, array $props, array &$data): void {
                foreach ($props as $property) {
                    if (!array_key_exists($property, $data)) {
                        continue;
                    }

                    $object->{$property} = $data[$property];
                    unset($data[$property]);
                }
            }, null, $class)($object, $props, $data);
        }
    }

    private function setRelationProperties(array $properties, RelationMap $relMap, object $object, array &$data): void
    {
        $refl = new \ReflectionClass($object);

        foreach ($properties as $class => $props) {
            if ($class === '') {
                continue;
            }

            Closure::bind(static function (object $object, array $props, array &$data) use ($refl, $relMap): void {
                foreach ($props as $property) {
                    if (!array_key_exists($property, $data)) {
                        continue;
                    }

                    $value = $data[$property];

                    if ($value instanceof ReferenceInterface && $refl->hasProperty($property)) {
                        $prop = $refl->getProperty($property);

                        if ($prop->hasType()) {
                            $types = $prop->getType() instanceof \ReflectionUnionType
                                ? array_map(fn($type) => $type->getName(), $prop->getType()->getTypes())
                                : [$prop->getType()->getName()];

                            if (!in_array($value::class, $types, true)) {
                                $relation = $relMap->getRelations()[$property] ?? null;
                                if ($relation !== null) {
                                    $value = $relation->collect($relation->resolve($value, true));
                                }
                            }
                        }
                    }

                    $object->{$property} = $value;

                    unset($data[$property]);
                }
            }, null, $class)($object, $props, $data);
        }
    }
}
