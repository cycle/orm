<?php

declare(strict_types=1);

namespace Cycle\ORM;

use Cycle\ORM\Command\Branch\ContextSequence;
use Cycle\ORM\Command\CommandInterface;
use Cycle\ORM\Command\ContextCarrierInterface as CC;
use Cycle\ORM\Heap\Node;
use Cycle\ORM\Heap\State;
use Cycle\ORM\Promise\PromiseInterface;
use Cycle\ORM\Promise\ReferenceInterface;
use Cycle\ORM\Relation\DependencyInterface;
use Cycle\ORM\Relation\RelationInterface;
use Cycle\ORM\Relation\ReversedRelationInterface;
use Cycle\ORM\Relation\ShadowBelongsTo;
use JetBrains\PhpStorm\ExpectedValues;

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
    /** @var RelationInterface[] */
    private array $outerDependencies = [];

    private static int $level = -1;

    public function __construct(array $innerRelations)
    {
        $this->innerRelations = $innerRelations;

        foreach ($this->innerRelations as $name => $relation) {
            if ($relation instanceof DependencyInterface) {
                $this->dependencies[$name] = $relation;
            } else {
                $this->slaves[$name] = $relation;
            }
        }
    }

    public function registerOuterRelation(string $role, string $container, array $relationSchema): void
    {
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
        if (\count($sequence) === 1) {
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

    public function setRelationsStatus(
        #[ExpectedValues(values: [RelationInterface::STATUS_PROCESSING, RelationInterface::STATUS_DEFERRED, RelationInterface::STATUS_RESOLVED])]
        int $status
    ): void {
        foreach ($this->dependencies  as $dependency) {
            $dependency->setStatus($status);
        }
        foreach ($this->slaves  as $slave) {
            $slave->setStatus($status);
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
        if ($command instanceof \Cycle\ORM\Command\Branch\Sequence) {
            echo "{$pad}> Added COMMAND SEQUENCE\n";
            ++self::$level;
            foreach ($command->getCommands() as $cmd) {
                $this->printCommand($cmd);
            }
            --self::$level;
            return;
        }
        if ($command instanceof \Cycle\ORM\Command\Branch\ContextSequence) {
            echo "{$pad}> Added CONTEXT COMMAND SEQUENCE\n";
            ++self::$level;
            foreach ($command->getCommands() as $cmd) {
                $this->printCommand($cmd);
            }
            --self::$level;
            return;
        }
        if ($command instanceof \Cycle\ORM\Command\Database\Update) {
            echo sprintf(
                "{$pad}> Added UPDATE command: where (%s) are (%s) SET keys (%s) data (%s)\n",
                implode(',', array_keys($command->getScope())),
                implode(',', $command->getScope()),
                implode(',', array_keys($command->getData())),
                implode(',', $command->getData())
            );
        } else {
            echo sprintf("{$pad}> Command %s\n", $command === null ? 'none' : get_class($command));
        }
    }
}
