<?php

declare(strict_types=1);

namespace Cycle\ORM\Service\Implementation;

use Cycle\ORM\EntityProxyInterface;
use Cycle\ORM\Exception\ORMException;
use Cycle\ORM\Heap\HeapInterface;
use Cycle\ORM\SchemaInterface;
use Cycle\ORM\Service\RoleResolverInterface;

/**
 * @internal
 */
final class RoleResolver implements RoleResolverInterface
{
    private SchemaInterface $schema;
    private HeapInterface $heap;

    public function __construct(SchemaInterface $schema, HeapInterface $heap)
    {
        $this->schema = $schema;
        $this->heap = $heap;
    }

    public function resolveRole(object|string $entity): string
    {
        if (\is_object($entity)) {
            $node = $this->heap->get($entity);
            if ($node !== null) {
                return $node->getRole();
            }

            /** @var class-string $class */
            $class = $entity::class;
            if (!$this->schema->defines($class)) {
                $parentClass = \get_parent_class($entity);

                if ($parentClass === false
                    || !$entity instanceof EntityProxyInterface
                    || !$this->schema->defines($parentClass)
                ) {
                    throw new ORMException("Unable to resolve role of `$class`.");
                }
                $class = $parentClass;
            }

            $entity = $class;
        }

        return $this->schema->resolveAlias($entity) ?? throw new ORMException("Unable to resolve role `$entity`.");
    }
}
