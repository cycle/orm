<?php

/**
 * Cycle DataMapper ORM
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

declare(strict_types=1);

namespace Cycle\ORM;

use Cycle\ORM\Command\Branch\ContextSequence;
use Cycle\ORM\Command\CommandInterface;
use Cycle\ORM\Command\ContextCarrierInterface as CC;
use Cycle\ORM\Heap\Node;
use Cycle\ORM\Relation\ChangesCheckerInterface;
use Cycle\ORM\Relation\DefaultChangesChecker;
use Cycle\ORM\Relation\DependencyInterface;
use Cycle\ORM\Relation\RelationInterface;

/**
 * Manages the position of node in the relation graph and provide access to neighbours.
 */
final class RelationMap
{
    /** @var ORMInterface @internal */
    private $orm;

    /** @var RelationInterface[] */
    private $relations = [];

    /** @var DependencyInterface[] */
    private $dependencies = [];

    /** @var DefaultChangesChecker|null */
    private static $defaultChangesChecker;

    /**
     * @param ORMInterface $orm
     * @param array        $relations
     */
    public function __construct(ORMInterface $orm, array $relations)
    {
        $this->orm = $orm;
        $this->relations = $relations;

        foreach ($this->relations as $name => $relation) {
            if ($relation instanceof DependencyInterface) {
                $this->dependencies[$name] = $relation;
            }
        }
    }

    /**
     * Init relation data in entity data and entity state.
     *
     * @param Node  $node
     * @param array $data
     *
     * @return array
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

    /**
     * Queue entity relations.
     *
     * @param CC     $parentStore
     * @param object $parentEntity
     * @param Node   $parentNode
     * @param array  $parentData
     *
     * @return CC
     */
    public function queueRelations(CC $parentStore, $parentEntity, Node $parentNode, array $parentData): CC
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
     * @param CC                $parentStore
     * @param object            $parentEntity
     * @param Node              $parentNode
     * @param RelationInterface $relation
     * @param mixed             $related
     * @param mixed             $original
     *
     * @return CommandInterface|null
     */
    private function queueRelation(
        CC $parentStore,
        $parentEntity,
        Node $parentNode,
        RelationInterface $relation,
        $related,
        $original
    ): ?CommandInterface {
        $changesChecker = $relation instanceof ChangesCheckerInterface ? $relation : self::getDefaultChangesChecker();
        if (!$changesChecker->hasChanges($related, $original)) {
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

    private static function getDefaultChangesChecker(): DefaultChangesChecker
    {
        if (null === self::$defaultChangesChecker) {
            self::$defaultChangesChecker = new DefaultChangesChecker();
        }

        return self::$defaultChangesChecker;
    }
}
