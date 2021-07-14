<?php

declare(strict_types=1);

namespace Cycle\ORM\Mapper\Hydrator;

use ReflectionClass;
use ReflectionProperty;

class ClassPropertiesExtractor
{
    /**
     * Extract all properties from given class
     * @param string|object $class
     * @param string[] $relations
     * @return array<string, ReflectionClass>
     * @throws \ReflectionException
     */
    public function extract($class, array $relations): array
    {
        $classProperties = [];
        $relationProperties = [];

        $originalClass = new ReflectionClass($class);

        $properties = $this->findAllInstanceProperties($originalClass);
        foreach ($properties as $property) {
            $className = $property->getDeclaringClass()->getName();
            $propertyName = $property->getName();

            if (in_array($propertyName, $relations)) {

                if ($property->isPrivate() || $property->isProtected()) {
                    $relationProperties[$className][$propertyName] = $propertyName;
                } else {
                    $relationProperties[PropertiesMap::PUBLIC_CLASS][$propertyName] = $propertyName;
                }
            } else if ($property->isPrivate() || $property->isProtected()) {
                $classProperties[$className][$propertyName] = $propertyName;
            } else {
                $classProperties[PropertiesMap::PUBLIC_CLASS][$propertyName] = $propertyName;
            }
        }

        return [
            'class' => new PropertiesMap($originalClass->getName(), $classProperties),
            'relations' => new PropertiesMap($originalClass->getName(), $relationProperties),
        ];
    }

    /**
     * Find all class properties recursively using class hierarchy without
     * removing name redefinitions
     *
     * @return ReflectionProperty[]
     */
    private function findAllInstanceProperties(?ReflectionClass $class = null): array
    {
        if (!$class) {
            return [];
        }

        return array_merge(
            $this->findAllInstanceProperties($class->getParentClass() ?: null), // of course PHP is shit.
            array_filter(
                $class->getProperties(),
                static function (ReflectionProperty $property): bool {
                    return !$property->isStatic();
                }
            )
        );
    }
}