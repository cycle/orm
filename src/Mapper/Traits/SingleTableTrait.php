<?php

declare(strict_types=1);

namespace Cycle\ORM\Mapper\Traits;

use Cycle\ORM\SchemaInterface;

/**
 * @internal
 */
trait SingleTableTrait
{
    protected string $discriminator = '_type';
    private SchemaInterface $schema;

    /**
     * Classname to represent entity.
     */
    protected function resolveClass(array $data, ?string $role = null): string
    {
        if ($role !== null && $role !== $this->role && $role !== $this->entity) {
            return $this->resolveChildClassByRole($role);
        }
        $class = $this->entity;
        if ($this->children !== [] && isset($data[$this->discriminator])) {
            $class = $this->children[$data[$this->discriminator]] ?? $this->entity;
        }

        return $class;
    }

    protected function getDiscriminatorValues(object $entity): array
    {
        $class = $entity::class;
        if ($class !== $this->entity) {
            // inheritance
            foreach ($this->children as $alias => $childClass) {
                if ($childClass === $class) {
                    return [$this->discriminator => $alias];
                }
            }
        }
        return [];
    }

    private function resolveChildClassByRole(string $role): string
    {
        $class = $this->schema->define($role, SchemaInterface::ENTITY);
        if (!\in_array($class, $this->children, true)) {
            throw new \InvalidArgumentException(
                \sprintf('Role `%s` does not have a child role `%s`.', $this->role, $role),
            );
        }
        return $class;
    }
}
