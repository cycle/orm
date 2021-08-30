<?php

declare(strict_types=1);

namespace Cycle\ORM\Mapper\Proxy\Hydrator;

use Closure;

class ClosureHydrator
{
    /**
     * @param array<string, PropertyMap> $propertyMaps Array of class properties
     * @param object $object
     * @param array $data
     *
     * @return object
     */
    public function hydrate(array $propertyMaps, object $object, array $data): object
    {
        $classProperties = $propertyMaps[ClassPropertiesExtractor::KEY_FIELDS]->getProperties();

        foreach ($classProperties as $class => $properties) {
            if ($class === '') {
                continue;
            }

            Closure::bind(static function (object $object, array $properties, array &$data): void {
                foreach ($properties as $property) {
                    if (!array_key_exists($property, $data)) {
                        continue;
                    }

                    $object->{$property} = $data[$property];
                    unset($data[$property]);
                }
            }, null, $class)($object, $properties, $data);
        }

        foreach ($data as $property => $value) {
            $object->{$property} = $value;
        }

        return $object;
    }
}
