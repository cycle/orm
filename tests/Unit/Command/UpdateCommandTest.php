<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Unit\Command;

use Cycle\Database\DatabaseInterface;
use Cycle\Database\Query\UpdateQuery;
use Cycle\ORM\Command\Database\Update;
use Cycle\ORM\Exception\CommandException;
use Cycle\ORM\Heap\Node;
use Cycle\ORM\Heap\State;
use Cycle\ORM\MapperInterface;
use Mockery as m;
use Testo\Assert;
use Testo\Assert\ExpectException;
use Testo\Lifecycle\BeforeTest;
use Testo\Test;

#[Test]
final class UpdateCommandTest
{
    private m\LegacyMockInterface|m\MockInterface|MapperInterface $mapper;
    private Update $cmd;
    private DatabaseInterface $db;
    private State $state;

    public function testIsEmpty(): void
    {
        Assert::true($this->cmd->isReady());
    }

    public function testHasData(): void
    {
        $this->state->register('name', 'new value');
        Assert::true($this->cmd->hasData());
    }

    public function testHasDataAppendix(): void
    {
        $this->cmd->registerAppendix('name', 'new value');
        Assert::false($this->cmd->hasData());
    }

    public function testHasDataColumn(): void
    {
        $this->cmd->registerColumn('name', 'new value');
        Assert::true($this->cmd->hasData());
    }

    public function testScopeSetter(): void
    {
        $this->cmd->waitScope('key');
        $this->cmd->setScope('key', 'value');
        Assert::same($this->cmd->getScope(), ['key' => 'value']);
        Assert::true($this->cmd->isScopeReady());
    }

    public function testSetNullScope(): void
    {
        $this->cmd->waitScope('key');
        $this->cmd->setScope('key', null);
        Assert::false($this->cmd->isScopeReady());
    }

    #[ExpectException(CommandException::class)]
    public function testExecuteWithNoScopeShouldThrowException(): void
    {
        $this->cmd->execute();
    }

    public function testExecuteWithAppendix(): void
    {
        $this->cmd->setScope('key', 'value');
        $this->cmd->registerAppendix('name', 'new value');

        $this->mapper->shouldReceive('uncast')
            ->with(['key' => 'value'])
            ->andReturn(['baz' => 'bar']);

        $this->mapper->shouldReceive('uncast')
            ->with(['name' => 'new value'])
            ->andReturn(['baz1' => 'bar']);

        $this->mapper->shouldReceive('mapColumns')
            ->with(['baz' => 'bar'])
            ->andReturn(['key' => 'scope']);

        $this->mapper->shouldReceive('mapColumns')
            ->with(['baz1' => 'bar'])
            ->andReturn(['column' => 'value']);

        $this->db->shouldReceive('update')
            ->once()
            ->with('table', ['column' => 'value'], ['key' => 'scope'])
            ->andReturn($query = m::mock(UpdateQuery::class));

        $query->shouldReceive('run')->once()->andReturn(5);

        $this->cmd->execute();

        Assert::same($this->state->getData(), ['foo' => 'bar', 'name' => 'new value']);
        Assert::same($this->cmd->getAffectedRows(), 5);
    }

    public function testExecuteWithEmptyDataShouldNotRunQuery(): void
    {
        $this->cmd->setScope('key', 'value');
        $this->cmd->registerAppendix('name', 'new value');

        $this->mapper->shouldReceive('uncast')
            ->with(['name' => 'new value'])
            ->andReturn(['name' => 'uncasted']);

        $this->mapper->shouldReceive('mapColumns')
            ->with(['name' => 'uncasted'])
            ->andReturn([]);

        $this->cmd->execute();

        Assert::same($this->state->getData(), ['foo' => 'bar', 'name' => 'new value']);
        Assert::same($this->cmd->getAffectedRows(), 0);
    }

    public function primaryKeysAreAwaitedUntilScopeIsSet(): void
    {
        $cmd = new Update($this->db, 'table', $this->state, $this->mapper, ['id']);

        Assert::false($cmd->isReady());

        $cmd->setScope('id', 1);

        Assert::true($cmd->isReady());
    }

    public function storeDataHoldsColumnsAndMappedChanges(): void
    {
        $this->cmd->registerColumn('type', 'admin');
        $this->cmd->registerAppendix('name', 'new value');
        $this->mapper->shouldReceive('mapColumns')
            ->once()
            ->with(['name' => 'new value'])
            ->andReturn(['user_name' => 'new value']);

        Assert::same($this->cmd->getStoreData(), ['type' => 'admin', 'user_name' => 'new value']);
        Assert::same($this->state->getData(), ['foo' => 'bar', 'name' => 'new value']);
    }

    public function storeDataWithoutMapperHoldsRawChanges(): void
    {
        $cmd = new Update($this->db, 'table', $this->state, null, []);
        $this->state->register('foo', 'changed');

        Assert::same($cmd->getStoreData(), ['foo' => 'changed']);
    }

    public function databaseIsNeededOnlyForScopedChanges(): void
    {
        Assert::null($this->cmd->getDatabase());

        $this->cmd->setScope('id', 1);

        Assert::null($this->cmd->getDatabase());

        $this->state->register('foo', 'changed');

        Assert::same($this->cmd->getDatabase(), $this->db);
    }

    public function unscopedChangesNeedNoDatabase(): void
    {
        $this->state->register('foo', 'changed');

        Assert::null($this->cmd->getDatabase());
    }

    #[BeforeTest]
    protected function setUp(): void
    {
        $this->mapper = m::mock(MapperInterface::class);
        $this->state = new State(Node::SCHEDULED_UPDATE, ['foo' => 'bar']);

        $this->cmd = new Update(
            $this->db = m::mock(DatabaseInterface::class),
            'table',
            $this->state,
            $this->mapper,
            [],
        );
    }
}
