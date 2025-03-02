<?php

declare(strict_types=1);

namespace Cycle\ORM\Service\Implementation;

use Cycle\ORM\Heap\HeapInterface;
use Cycle\ORM\Heap\Node;
use Cycle\ORM\Reference\ReferenceInterface;
use Cycle\ORM\Service\EntityFactoryInterface;
use Cycle\ORM\Service\IndexProviderInterface;
use Cycle\ORM\Service\MapperProviderInterface;
use Cycle\ORM\Service\RelationProviderInterface;
use Cycle\ORM\SchemaInterface;
use Cycle\ORM\Select\LoaderInterface;
use Cycle\ORM\Service\RoleResolverInterface;

/**
 * @internal
 */
final class EntityFactory implements EntityFactoryInterface
{
    public function __construct(
        private HeapInterface $heap,
        private SchemaInterface $schema,
        private MapperProviderInterface $mapperProvider,
        private RelationProviderInterface $relationProvider,
        private IndexProviderInterface $indexProvider,
        private RoleResolverInterface $roleResolver,
    ) {}

    public function make(
        string $role,
        array $data = [],
        int $status = Node::NEW,
        bool $typecast = false,
    ): object {
        $role = $data[LoaderInterface::ROLE_KEY] ?? $role;
        unset($data[LoaderInterface::ROLE_KEY]);
        // Resolved role
        $rRole = $this->roleResolver->resolveRole($role);
        $relMap = $this->relationProvider->getRelationMap($rRole);
        $mapper = $this->mapperProvider->getMapper($rRole);

        $castedData = $typecast ? $mapper->cast($data) : $data;

        if ($status !== Node::NEW) {
            // unique entity identifier
            $pk = $this->schema->define($role, SchemaInterface::PRIMARY_KEY);
            if (\is_array($pk)) {
                $ids = [];
                foreach ($pk as $key) {
                    if (!isset($data[$key])) {
                        $ids = null;
                        break;
                    }
                    $ids[$key] = $data[$key];
                }
            } else {
                $ids = isset($data[$pk]) ? [$pk => $data[$pk]] : null;
            }

            if ($ids !== null) {
                $e = $this->heap->find($rRole, $ids);

                if ($e !== null) {
                    // Get not resolved relations (references)
                    $refs = \array_filter(
                        $mapper->fetchRelations($e),
                        fn($v) => $v instanceof ReferenceInterface,
                    );

                    if ($refs === []) {
                        return $e;
                    }

                    $node = $this->heap->get($e);
                    \assert($node !== null);

                    // Replace references with actual relation data
                    return $mapper->hydrate($e, $relMap->init(
                        $this,
                        $node,
                        \array_intersect_key($castedData, $refs),
                    ));
                }
            }
        }

        $node = new Node($status, $castedData, $rRole);
        $e = $mapper->init($data, $role);

        /** Entity should be attached before {@see RelationMap::init()} running */
        $this->heap->attach($e, $node, $this->indexProvider->getIndexes($rRole));

        return $mapper->hydrate($e, $relMap->init($this, $node, $castedData));
    }
}
