<?php

declare(strict_types=1);

namespace Cycle\ORM\Mapper\Proxy\Hydrator;

use ReflectionClass;
use ReflectionProperty;

class ClassPropertiesExtractor
{
    public const KEY_FIELDS = 'class';
    public const KEY_RELATIONS = 'relations';

    /**
     * Extract all properties from given class
     *
     * @param string|object $objectOrClass
     * @param string[] $relations
     *
     * @return array<string, ReflectionClass>
     *
     * @throws \ReflectionException
     */
    public function extract($objectOrClass, array $relations): array
    {
        $classProperties = [];
        $relationProperties = [];

        $reflection = new ReflectionClass($objectOrClass);

        $properties = $this->findAllInstanceProperties($reflection);
        foreach ($properties as $property) {
            $className = $property->getDeclaringClass()->getName();
            $propertyName = $property->getName();

            if (in_array($propertyName, $relations)) {
                $relationProperties[$property->isPrivate() ? $className : PropertyMap::PUBLIC_CLASS][$propertyName] = $propertyName;
            } else {
                $classProperties[$property->isPublic() ? PropertyMap::PUBLIC_CLASS : $className][$propertyName] = $propertyName;
            }
        }

        return [
            self::KEY_FIELDS => new PropertyMap($reflection->getName(), $classProperties),
            self::KEY_RELATIONS => new PropertyMap($reflection->getName(), $relationProperties),
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
        if ($class === null) {
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
