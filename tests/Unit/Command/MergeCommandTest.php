<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Unit\Command;

use Cycle\Database\DatabaseInterface;
use Cycle\ORM\Command\CommandInterface;
use Cycle\ORM\Command\Database\Insert;
use Cycle\ORM\Command\Special\MergeCommand;
use Cycle\ORM\Command\Special\Sequence;
use Cycle\ORM\Command\Special\WrappedCommand;
use Cycle\ORM\Command\Special\WrappedStoreCommand;
use Cycle\ORM\Heap\Node;
use Cycle\ORM\Heap\State;
use Mockery as m;
use Testo\Assert;
use Testo\Expect;
use Testo\Test;

#[Test]
final class MergeCommandTest
{
    public function primaryCommandMustStoreData(): void
    {
        Expect::exception(\InvalidArgumentException::class);

        new MergeCommand(WrappedCommand::wrapCommand(m::mock(CommandInterface::class)));
    }

    public function primaryCommandIsTakenFromSequence(): void
    {
        $primary = $this->insert($this->db());

        $command = new MergeCommand(new Sequence($primary));

        Assert::same($command->getPrimaryCommand(), $primary);
    }

    public function primaryCommandIsTakenFromAnotherMerge(): void
    {
        $primary = $this->insert($this->db());

        $command = new MergeCommand(new MergeCommand($primary));

        Assert::same($command->getPrimaryCommand(), $primary);
    }

    public function stateComesFromPrimaryCommand(): void
    {
        $db = $this->db();
        $primary = $this->insert($db);
        $command = new MergeCommand($primary);

        Assert::true($command->isReady());
        Assert::false($command->isExecuted());
        Assert::same($command->getDatabase(), $db);

        $command->execute();

        Assert::false($primary->isExecuted());
    }

    public function addedCommandsAreCounted(): void
    {
        $command = new MergeCommand($this->insert($this->db()));
        $child = $this->insert($this->db());

        $command->addCommand($child);

        Assert::same($command->getCommands(), [$child]);
        Assert::count($command, 1);
    }

    public function commandsOfAnotherDatabaseAreYieldedAfterPrimary(): void
    {
        $db = $this->db();
        $primary = $this->insert($this->db(), ['id' => 1]);
        $insert = $this->insert($db, ['name' => 'foreign']);
        $wrapped = WrappedStoreCommand::wrapStoreCommand($this->insert($db, ['name' => 'wrapped']));
        $command = new MergeCommand($primary);
        $command->addCommand($insert);
        $command->addCommand($wrapped);

        $commands = \iterator_to_array($command, false);

        Assert::same($commands, [$primary, $insert, $wrapped]);
        Assert::same($primary->getStoreData(), ['id' => 1]);
        Assert::same($insert->getDatabase(), $db);
    }

    private function db(): DatabaseInterface
    {
        return m::mock(DatabaseInterface::class);
    }

    private function insert(DatabaseInterface $db, array $data = []): Insert
    {
        return new Insert($db, 'table', new State(Node::SCHEDULED_INSERT, $data), null);
    }
}
