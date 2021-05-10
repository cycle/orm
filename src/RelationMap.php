<?php

declare(strict_types=1);

namespace Cycle\ORM;

use Cycle\ORM\Command\Branch\ContextSequence;
use Cycle\ORM\Command\CommandInterface;
use Cycle\ORM\Command\ContextCarrierInterface as CC;
use Cycle\ORM\Heap\Node;
use Cycle\ORM\Promise\PromiseInterface;
use Cycle\ORM\Promise\ReferenceInterface;
use Cycle\ORM\Relation\DependencyInterface;
use Cycle\ORM\Relation\RelationInterface;

/**
 * Manages the position of node in the relation graph and provide access to neighbours.
 */
final class RelationMap
{
    /** @var RelationInterface[] */
    private array $relations;

    /** @var DependencyInterface[] */
    private array $dependencies = [];

    public function __construct(array $relations)
    {
        $this->relations = $relations;

        foreach ($this->relations as $name => $relation) {
            if ($relation instanceof DependencyInterface) {
                $this->dependencies[$name] = $relation;
            }
        }
    }

    /**
     * Init relation data in entity data and entity state.
     */
    public function init(Node $node, array $data): array
    {
        foreach ($this->relations as $name => $relation) {
            if (!array_key_exists($name, $data)) {
                if ($node->hasRelation($name)) {
                    continue;
                }

                [$data[$name], $orig] = $relation->initPromise($node);
                $node->setRelation($name, $orig);
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
            $node->setRelation($name, $orig);
        }

        return $data;
    }

    public function queueRelations(CC $parentStore, object $parentEntity, Node $parentNode, array $parentData): CC
    {
        $state = $parentNode->getState();
        $sequence = new ContextSequence();

        // queue all "left" graph branches
        foreach ($this->dependencies as $name => $relation) {
            if (!$relation->isCascade() || $parentNode->getState()->visited($name)) {
                continue;
            }
            $state->markVisited($name);

            $command = $this->queueRelation(
                $parentStore,
                $parentEntity,
                $parentNode,
                $relation,
                $relation->extract($parentData[$name] ?? null),
                $parentNode->getRelation($name)
            );

            if ($command !== null) {
                $sequence->addCommand($command);
            }
        }

        // queue target entity
        $sequence->addPrimary($parentStore);

        // queue all "right" graph branches
        foreach ($this->relations as $name => $relation) {
            if (!$relation->isCascade() || $parentNode->getState()->visited($name)) {
                continue;
            }
            $state->markVisited($name);

            $command = $this->queueRelation(
                $parentStore,
                $parentEntity,
                $parentNode,
                $relation,
                $relation->extract($parentData[$name] ?? null),
                $parentNode->getRelation($name)
            );

            if ($command !== null) {
                $sequence->addCommand($command);
            }
        }

        if (\count($sequence) === 1) {
            return current($sequence->getCommands());
        }

        return $sequence;
    }

    /**
     * Queue the relation.
     *
     * @param object|array|null $related
     * @param object|array|null $original
     */
    private function queueRelation(
        CC $parentStore,
        object $parentEntity,
        Node $parentNode,
        RelationInterface $relation,
        $related,
        $original
    ): ?CommandInterface {
        if (
            ($related instanceof ReferenceInterface || $related === null)
            && !($related instanceof PromiseInterface && $related->__loaded())
            && $related === $original
        ) {
            // no changes in non changed promised relation
            return null;
        }

        $relStore = $relation->queue(
            $parentStore,
            $parentEntity,
            $parentNode,
            $related,
            $original
        );

        // update current relation state
        $parentNode->getState()->setRelation($relation->getName(), $related);

        return $relStore;
    }

    /**
     * Check if both references are equal.
     *
     * @param mixed $a
     * @param mixed $b
     */
    private function sameReference($a, $b): bool
    {
        if (!$a instanceof ReferenceInterface || !$b instanceof ReferenceInterface) {
            return false;
        }

        return $a->__role() === $b->__role() && $a->__scope() === $b->__scope();
    }
}
