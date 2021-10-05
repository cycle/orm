<?php

declare(strict_types=1);

namespace Cycle\ORM\Transaction;

use Cycle\ORM\Command\CommandInterface;
use Cycle\ORM\Command\Special\Sequence;
use Cycle\ORM\Command\Special\WrappedStoreCommand;
use Cycle\ORM\Command\StoreCommandInterface;
use Cycle\ORM\Heap\Node;
use Cycle\ORM\ORMInterface;
use Cycle\ORM\SchemaInterface;

class CommandGenerator implements CommandGeneratorInterface
{
    public function generateStoreCommand(ORMInterface $orm, Tuple $tuple): CommandInterface
    {
        $isNew = $tuple->node->getStatus() === Node::NEW;
        $tuple->state->setStatus($isNew ? Node::SCHEDULED_INSERT : Node::SCHEDULED_UPDATE);
        $schema = $orm->getSchema();

        $commands = $this->storeParents($orm, $tuple, $isNew);
        $commands[$tuple->node->getRole()] = $this->storeEntity($tuple, $isNew);

        return \count($commands) === 1
            ? \current($commands)
            : $this->buildStoreSequence($schema, $commands, $tuple);
    }

    /**
     * @return array<string, CommandInterface>
     */
    protected function storeParents(ORMInterface $orm, Tuple $tuple, bool $isNew): array
    {
        $schema = $orm->getSchema();
        $parents = $commands = [];
        $parent = $schema->define($tuple->node->getRole(), SchemaInterface::PARENT);
        while (is_string($parent)) {
            array_unshift($parents, $parent);
            $parent = $schema->define($parent, SchemaInterface::PARENT);
        }
        foreach ($parents as $parent) {
            $command = $this->generateParentStoreCommand($orm, $tuple, $parent, $isNew);
            if ($command !== null) {
                $commands[$parent] = $command;
            }
        }

        return $commands;
    }

    protected function storeEntity(Tuple $tuple, bool $isNew): ?CommandInterface
    {
        return $isNew
            ? $tuple->mapper->queueCreate($tuple->entity, $tuple->node, $tuple->state)
            : $tuple->mapper->queueUpdate($tuple->entity, $tuple->node, $tuple->state);
    }

    protected function generateParentStoreCommand(
        ORMInterface $orm,
        Tuple $tuple,
        string $parentRole,
        bool $isNew
    ): ?CommandInterface {
        $parentMapper = $orm->getMapper($parentRole);
        return $isNew
            ? $parentMapper->queueCreate($tuple->entity, $tuple->node, $tuple->state)
            : $parentMapper->queueUpdate($tuple->entity, $tuple->node, $tuple->state);
    }

    public function generateDeleteCommand(ORMInterface $orm, Tuple $tuple): CommandInterface
    {
        // currently we rely on db to delete all nested records (or soft deletes)
        return $tuple->mapper->queueDelete($tuple->entity, $tuple->node, $tuple->node->getState());
    }

    /**
     * @param array<string, StoreCommandInterface> $commands
     */
    private function buildStoreSequence(SchemaInterface $schema, array $commands, Tuple $tuple): CommandInterface
    {
        $parent = null;
        $result = [];
        foreach ($commands as $role => $command) {
            // Current parent has no parent
            if ($parent === null) {
                $result[] = $command;
                $parent = $role;
                continue;
            }

            $command = WrappedStoreCommand::wrapStoreCommand($command);

            // Transact PK from previous parent to current
            $parentKey = (array)($schema->define($role, SchemaInterface::PARENT_KEY)
                ?? $schema->define($parent, SchemaInterface::PRIMARY_KEY));
            $primaryKey = (array)$schema->define($role, SchemaInterface::PRIMARY_KEY);
            $result[] = $command->withBeforeExecution(
                static function (StoreCommandInterface $command) use ($tuple, $parentKey, $primaryKey): void {
                    foreach ($primaryKey as $i => $pk) {
                        $command->registerAppendix($pk, $tuple->state->getValue($parentKey[$i]));
                    }
                }
            );
            $parent = $role;
        }

        return (new Sequence())->addCommand(...$result);
    }
}
