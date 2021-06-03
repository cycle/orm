<?php

declare(strict_types=1);

namespace Cycle\ORM;

use Cycle\ORM\Command\Branch\ContextSequence;
use Cycle\ORM\Command\Branch\Sequence;
use Cycle\ORM\Command\CommandInterface;
use Cycle\ORM\Command\ContextCarrierInterface as CC;
use Cycle\ORM\Command\Database\Update;
use Cycle\ORM\Heap\Node;
use Cycle\ORM\Heap\State;
use Cycle\ORM\Promise\PromiseInterface;
use Cycle\ORM\Promise\ReferenceInterface;
use Cycle\ORM\Relation\DependencyInterface;
use Cycle\ORM\Relation\Embedded;
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

    private static int $level = -1;

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
        // skip Morphed
        if ($relationType === Relation::MORPHED_HAS_ONE || $relationType === Relation::MORPHED_HAS_MANY) {
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
    public function init(Node $node, array $data): array
    {
        foreach ($this->innerRelations as $name => $relation) {
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
        $pad = str_repeat('    ', ++self::$level);

        $state = $parentNode->getState();
        $sequence = new ContextSequence();

        // queue all "left" graph branches
        foreach ($this->dependencies as $name => $relation) {
            echo "\n{$pad}Dependency {$parentNode->getRole()}:{$name}\n";
            if (!$relation->isCascade() || $parentNode->getState()->visited($name)) {
                echo "{$pad}Not cascade\n";
                continue;
            }
            $state->markVisited($name);

            $command = $this->queueRelation(
                // $parentStore,
                $parentEntity,
                $parentNode,
                $relation,
                $relation->extract($parentData[$name] ?? null),
                $parentNode->getRelation($name)
            );

            $this->printCommand($command);

            if ($command !== null) {
                $sequence->addCommand($command);
            }
        }

        // queue target entity
        $sequence->addPrimary($parentStore);

        // queue all "right" graph branches
        foreach ($this->innerRelations as $name => $relation) {
            echo "\n{$pad}Relation {$parentNode->getRole()}:{$name}\n";
            if (!$relation->isCascade() || $parentNode->getState()->visited($name)) {
                echo "{$pad}Not cascade\n";
                continue;
            }
            $state->markVisited($name);

            $command = $this->queueRelation(
                // $parentStore,
                $parentEntity,
                $parentNode,
                $relation,
                $relation->extract($parentData[$name] ?? null),
                $parentNode->getRelation($name)
            );

            $this->printCommand($command);

            if ($command !== null) {
                $sequence->addCommand($command);
            }
        }

        self::$level--;
        if (count($sequence) === 1) {
            return current($sequence->getCommands());
        }

        return $sequence;
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

    public function setRelationsStatus(
        Node $node,
        #[ExpectedValues(values: [RelationInterface::STATUS_PREPARE, RelationInterface::STATUS_DEFERRED, RelationInterface::STATUS_RESOLVED])]
        int $status
    ): void {
        foreach ($this->dependencies  as $dependency) {
            $node->setRelationStatus($dependency->getName(), $status);
        }
        foreach ($this->slaves  as $slave) {
            $node->setRelationStatus($slave->getName(), $status);
        }
    }

    /**
     * Queue the relation.
     *
     * @param object|array|null $related
     * @param object|array|null $original
     */
    private function queueRelation(
        // CC $parentStore,
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

    private function printCommand(?CommandInterface $command): void
    {
        $pad = str_repeat('    ', self::$level);
        if ($command instanceof Sequence) {
            echo "{$pad}> Added COMMAND SEQUENCE\n";
            ++self::$level;
            foreach ($command->getCommands() as $cmd) {
                $this->printCommand($cmd);
            }
            --self::$level;
            return;
        }
        if ($command instanceof ContextSequence) {
            echo "{$pad}> Added CONTEXT COMMAND SEQUENCE\n";
            ++self::$level;
            foreach ($command->getCommands() as $cmd) {
                $this->printCommand($cmd);
            }
            --self::$level;
            return;
        }
        if ($command instanceof Update) {
            echo sprintf(
                "{$pad}> Added UPDATE command: where (%s) are (%s) SET ...?\n",
                implode(',', array_keys($command->getScope())),
                implode(',', $command->getScope())
            );
        } else {
            echo sprintf("{$pad}> Command %s\n", $command === null ? 'none' : get_class($command));
        }
    }
}
