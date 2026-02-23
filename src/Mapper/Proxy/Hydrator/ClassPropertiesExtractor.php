<?php

declare(strict_types=1);

namespace Cycle\ORM\Mapper\Proxy\Hydrator;

/**
 * @internal
 */
class ClassPropertiesExtractor
{
    public const KEY_FIELDS = 'class';
    public const KEY_RELATIONS = 'relations';

    /**
     * Extract all properties from given class
     *
     * @param string[] $relations
     *
     * @return array<string, PropertyMap>
     * @throws \ReflectionException
     */
    public function extract(string|object $objectOrClass, array $relations): array
    {
        $classProperties = [];
        $relationProperties = [];

        $reflection = new \ReflectionClass($objectOrClass);

        $properties = $this->findAllInstanceProperties($reflection);
        foreach ($properties as $property) {
            $className = $property->getDeclaringClass()->getName();
            $propertyName = $property->getName();

            $class = $property->isPublic() && !$this->hasRestrictedSet($property)
                ? PropertyMap::PUBLIC_CLASS
                : $className;

            if (\in_array($propertyName, $relations, true)) {
                $relationProperties[$class][$propertyName] = $propertyName;
            } else {
                $classProperties[$class][$propertyName] = $propertyName;
            }
        }

        return [
            self::KEY_FIELDS => new PropertyMap($reflection->getName(), $classProperties),
            self::KEY_RELATIONS => new PropertyMap($reflection->getName(), $relationProperties),
        ];
    }

    /**
     * Check if a property has restricted (private or protected) set visibility.
     *
     * PHP 8.4 asymmetric visibility: `private(set)` properties return true for
     * isPublic() (read visibility) but cannot be assigned from outside the class.
     * ClosureHydrator skips PUBLIC_CLASS properties and the fallback `@$entity->$prop = $value`
     * silently fails because set visibility is private.
     */
    private function hasRestrictedSet(\ReflectionProperty $property): bool
    {
        if (\PHP_VERSION_ID < 80400) {
            return false;
        }

        return $property->isPrivateSet() || $property->isProtectedSet();
    }

    /**
     * Find all class properties recursively using class hierarchy without
     * removing name redefinitions
     *
     * @return \ReflectionProperty[]
     */
    private function findAllInstanceProperties(?\ReflectionClass $class = null): array
    {
        if ($class === null) {
            return [];
        }

        return \array_merge(
            $this->findAllInstanceProperties($class->getParentClass() ?: null),
            \array_filter(
                $class->getProperties(),
                static fn(\ReflectionProperty $property): bool => !$property->isStatic(),
            ),
        );
    }
}
