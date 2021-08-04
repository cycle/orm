<?php

declare(strict_types=1);

namespace Cycle\ORM\Mapper\Traits;

trait SingleTableTrait
{
    protected string $discriminator = '_type';

    /**
     * Classname to represent entity.
     */
    protected function resolveClass(array $data): string
    {
        $class = $this->entity;
        if ($this->children !== [] && isset($data[$this->discriminator])) {
            $class = $this->children[$data[$this->discriminator]] ?? $this->entity;
        }

        return $class;
    }

    protected function getDiscriminatorValues(object $entity): array
    {
        $class = get_class($entity);
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
}
