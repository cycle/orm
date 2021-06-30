<?php

declare(strict_types=1);

namespace Cycle\ORM;

use Cycle\ORM\Heap\HeapInterface;
use Cycle\ORM\Heap\Node;
use Cycle\ORM\Promise\Deferred;
use Cycle\ORM\Relation\DependencyInterface;
use Cycle\ORM\Relation\RelationInterface;
use Cycle\ORM\Relation\SameRowRelationInterface;
use Cycle\ORM\Relation\ShadowBelongsTo;
use JetBrains\PhpStorm\ExpectedValues;

use function count;

/**
 * Manages the position of node in the relation graph and provide access to neighbours.
 */
final class RelationMap
{
    /** @var RelationInterface[] */
    private array $innerRelations;

    /** @var DependencyInterface[] */
    private array $dependencies = [];
    /** @var RelationInterface[] */
    private array $slaves = [];
    /** @var SameRowRelationInterface[] */
    private array $embedded = [];

    public function __construct(array $innerRelations, array $outerRelations)
    {
        $this->innerRelations = $innerRelations;

        foreach ($innerRelations as $name => $relation) {
            if ($relation instanceof DependencyInterface) {
                $this->dependencies[$name] = $relation;
            } elseif ($relation instanceof SameRowRelationInterface) {
                $this->embedded[$name] = $relation;
            } else {
                $this->slaves[$name] = $relation;
            }
        }

        foreach ($outerRelations as $outerRole => $relations) {
            foreach ($relations as $container => $relationSchema) {
                $this->registerOuterRelation($outerRole, $container, $relationSchema);
            }
        }
    }

    private function registerOuterRelation(string $role, string $container, array $relationSchema): void
    {
        // todo: it better to check instanceOf \Cycle\ORM\Relation\DependencyInterface instead of int
        $relationType = $relationSchema[Relation::TYPE];
        // skip dependencies
        if ($relationType === Relation::BELONGS_TO || $relationType === Relation::REFERS_TO) {
            return;
        }
        if ($relationType === Relation::MANY_TO_MANY) {
            // $schema = $relationSchema[Relation::SCHEMA];
            // $through = $this->schema->resolveAlias($schema[Relation::THROUGH_ENTITY]);
            # todo: SHADOW_HAS_MANY
            return;
        }
        if ($relationType === Relation::MORPHED_HAS_ONE || $relationType === Relation::MORPHED_HAS_MANY) {
            // todo: find morphed collisions, decide handshake
            $relation = new ShadowBelongsTo('~morphed~', $container, $relationSchema);
            $this->dependencies[$relation->getName()] ??= $relation;
            return;
        }
        // $schema = $relationSchema[Relation::SCHEMA];
        // // $cascade = $schema[Relation::CASCADE];
        // $outerKeys = (array)$schema[Relation::OUTER_KEY];
        // $innerKeys = (array)$schema[Relation::INNER_KEY];
        // foreach ($this->innerRelations as $relation) {
        //
        // }
        $relation = new ShadowBelongsTo($role, $container, $relationSchema);
        $this->dependencies[$relation->getName()] = $relation;
    }

    public function hasDependencies(): bool
    {
        return count($this->dependencies) > 0;
    }

    public function hasSlaves(): bool
    {
        return count($this->slaves) > 0;
    }

    public function hasEmbedded(): bool
    {
        return count($this->embedded) > 0;
    }

    /**
     * Init relation data in entity data and entity state.
     */
    public function init(HeapInterface $heap, Node $node, array $data): array
    {
        foreach ($this->innerRelations as $name => $relation) {
            if (!array_key_exists($name, $data)) {
                if ($node->hasRelation($name)) {
                    continue;
                }

                // [$data[$name], $orig] = $relation->initPromise($node);
                # todo: find in heap
                $data[$name] = $relation->initDeferred($node);
                $node->setRelation($name, $data[$name]);
                continue;
            }

            $item = $data[$name];
            if (is_object($item) || $item === null) {
                // cyclic initialization
                $node->setRelation($name, $item);
                continue;
            }

            // init relation for the entity and for state and the same time
            [$data[$name], $orig] = $relation->init($node, $item);

            if ($data[$name] instanceof Deferred && $data[$name]->isLoaded()) {
                $node->setRelation($name, $data[$name]->getOrigin());
                $data[$name] = $data[$name]->getData();
            } else {
                $node->setRelation($name, $data[$name]);
            }
        }

        return $data;
    }

    /**
     * @return RelationInterface[]
     */
    public function getSlaves(): array
    {
        return $this->slaves;
    }

    /**
     * @return DependencyInterface[]
     */
    public function getMasters(): array
    {
        return $this->dependencies;
    }

    /**
     * @return SameRowRelationInterface[]
     */
    public function getEmbedded(): array
    {
        return $this->embedded;
    }

    /**
     * @return RelationInterface[]
     */
    public function getRelations(): array
    {
        return $this->innerRelations;
    }
}
