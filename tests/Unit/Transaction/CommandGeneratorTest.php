<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Unit\Transaction;

use Cycle\ORM\Command\StoreCommandInterface;
use Cycle\ORM\Heap\Node;
use Cycle\ORM\Heap\State;
use Cycle\ORM\MapperInterface;
use Cycle\ORM\ORMInterface;
use Cycle\ORM\SchemaInterface;
use Cycle\ORM\Transaction\CommandGenerator;
use Cycle\ORM\Transaction\Tuple;
use Mockery as m;
use Testo\Assert;
use Testo\Test;

#[Test]
final class CommandGeneratorTest
{
    public function newEntityIsScheduledForInsert(): void
    {
        $command = m::mock(StoreCommandInterface::class);
        $tuple = $this->tuple(Node::NEW, create: $command);

        $result = (new CommandGenerator())->generateStoreCommand($this->orm(), $tuple);

        Assert::same($result, $command);
        Assert::same($tuple->state->getStatus(), Node::SCHEDULED_INSERT);
    }

    public function managedEntityIsScheduledForUpdate(): void
    {
        $command = m::mock(StoreCommandInterface::class);
        $tuple = $this->tuple(Node::MANAGED, update: $command);

        $result = (new CommandGenerator())->generateStoreCommand($this->orm(), $tuple);

        Assert::same($result, $command);
        Assert::same($tuple->state->getStatus(), Node::SCHEDULED_UPDATE);
    }

    private function orm(): ORMInterface
    {
        $schema = m::mock(SchemaInterface::class);
        $schema->allows('define')->with('user', SchemaInterface::PARENT)->andReturn(null);
        $orm = m::mock(ORMInterface::class);
        $orm->allows('getSchema')->andReturn($schema);
        return $orm;
    }

    private function tuple(
        int $status,
        ?StoreCommandInterface $create = null,
        ?StoreCommandInterface $update = null,
    ): Tuple {
        $tuple = new Tuple(Tuple::TASK_STORE, new \stdClass(), true, Tuple::STATUS_PREPARING);
        $tuple->node = new Node($status, [], 'user');
        $tuple->state = new State($status, []);
        $mapper = m::mock(MapperInterface::class);
        $mapper->allows('queueCreate')->andReturn($create);
        $mapper->allows('queueUpdate')->andReturn($update);
        $tuple->mapper = $mapper;
        return $tuple;
    }
}
