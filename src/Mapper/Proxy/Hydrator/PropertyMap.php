<?php

declare(strict_types=1);

namespace Cycle\ORM\Mapper\Proxy\Hydrator;

/**
 * @internal
 */
class PropertyMap
{
    public const PUBLIC_CLASS = '';

    public function __construct(
        private string $class,
        private array $properties,
    ) {}

    public function isPublicProperty(string $name): bool
    {
        return $this->getPropertyClass($name) === static::PUBLIC_CLASS;
    }

    public function getPropertyClass(string $name): ?string
    {
        foreach ($this->properties as $class => $properties) {
            if (\in_array($name, $properties, true)) {
                return $class;
            }
        }

        return null;
    }

    public function getProperties(): array
    {
        return $this->properties;
    }

    public function getClass(): string
    {
        return $this->class;
    }
}
