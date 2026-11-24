<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Unit\Command;

use Cycle\Database\DatabaseInterface;
use Cycle\Database\Driver\CompilerInterface;
use Cycle\Database\Driver\DriverInterface;
use Cycle\Database\Injection\FragmentInterface;
use Cycle\Database\Query\QueryParameters;
use Cycle\ORM\Command\Database\Insert;
use Cycle\ORM\Heap\Node;
use Cycle\ORM\Heap\State;
use Cycle\ORM\MapperInterface;
use Cycle\ORM\Schema\GeneratedField;
use Cycle\ORM\Tests\Fixtures\TestInsertCommand;
use Cycle\ORM\Tests\Fixtures\TestInsertCommandWithReturning;
use Mockery as m;
use Testo\Assert;
use Testo\Lifecycle\BeforeTest;
use Testo\Test;

#[Test]
final class InsertCommandTest
{
    private Insert $cmd;
    private m\LegacyMockInterface|m\MockInterface|DatabaseInterface $db;
    private m\LegacyMockInterface|m\MockInterface|MapperInterface $mapper;
    private State $state;

    public function testDatabase(): void
    {
        Assert::same($this->cmd->getDatabase(), $this->db);
    }

    public function testIsReady(): void
    {
        Assert::true($this->cmd->isReady());
    }

    public function testCommandWithoutReturningInterfaceShouldNotUseIt(): void
    {
        $table = 'table';

        $insertQuery = (new TestInsertCommand($table))
            ->withDriver($driver = m::mock(DriverInterface::class), '');

        $driver->shouldReceive('getQueryCompiler')
            ->once()
            ->andReturn($compiler = m::mock(CompilerInterface::class));

        $driver->shouldReceive('execute')->once();
        $driver->shouldReceive('lastInsertID')->once()->andReturn(123);

        $compiler->shouldReceive('compile')->once()->withArgs(
            function (QueryParameters $params, string $prefix, FragmentInterface $fragment) {
                return true;
            },
        );

        $this->mapper->shouldReceive('uncast')
            ->once()
            ->with($this->state->getData())
            ->andReturn(['foo' => 'baaar']);

        $this->mapper->shouldReceive('mapColumns')
            ->once()
            ->with(['foo' => 'baaar'])
            ->andReturn(['baz' => 'bar']);

        $this->mapper->shouldReceive('cast')
            ->once()
            ->with(['id' => 123])
            ->andReturn(['id' => 234]);

        $this->db->shouldReceive('insert')->once()->with($table)->andReturn($insertQuery);
        $this->cmd->execute();

        Assert::same($this->state->getValue('id'), 234);
    }

    public function testCommandWithReturningInterfaceWithoutPkColumnShouldNotUseIt(): void
    {
        $class = new \ReflectionClass($this->cmd);
        $property = $class->getProperty('pkColumn');
        PHP_VERSION_ID < 80100 and $property->setAccessible(true);
        $property->setValue($this->cmd, null);

        $table = 'table';

        $this->mapper->shouldReceive('uncast')
            ->once()
            ->with($this->state->getData())
            ->andReturn(['foo' => 'baaar']);

        $this->mapper->shouldReceive('mapColumns')
            ->once()
            ->with(['foo' => 'baaar'])
            ->andReturn(['baz' => 'bar']);

        $this->mapper->shouldReceive('cast')
            ->once()
            ->with(['id' => 345])
            ->andReturn(['id' => 234]);

        $this->db->shouldReceive('insert')
            ->once()
            ->with($table)
            ->andReturn($insertQuery = m::mock(TestInsertCommandWithReturning::class));

        $insertQuery->shouldReceive('values')->once()->andReturnSelf();
        $insertQuery->shouldReceive('run')->once()->andReturn(345);
        $this->cmd->execute();

        Assert::same($this->state->getValue('id'), 234);
    }

    public function testCommandWithReturningInterfaceShouldUseIt(): void
    {
        $table = 'table';

        $this->mapper->shouldReceive('uncast')
            ->once()
            ->with($this->state->getData())
            ->andReturn(['foo' => 'baaar']);

        $this->mapper->shouldReceive('mapColumns')
            ->once()
            ->with(['foo' => 'baaar'])
            ->andReturn(['baz' => 'bar']);

        $this->mapper->shouldReceive('mapColumns')
            ->once()
            ->with(['id' => 'foo_id'])
            ->andReturn(['foo_id' => 'foo_id']);

        $this->mapper->shouldReceive('cast')
            ->once()
            ->with(['id' => 234])
            ->andReturn(['id' => 324]);

        $this->db->shouldReceive('insert')
            ->once()
            ->with($table)
            ->andReturn($insertQuery = m::mock(TestInsertCommandWithReturning::class));

        $insertQuery->shouldReceive('values')->once()->andReturnSelf();
        $insertQuery->shouldReceive('returning')->once()->with('foo_id')->andReturnSelf();
        $insertQuery->shouldReceive('run')->once()->andReturn(234);

        $this->cmd->execute();

        Assert::same($this->state->getValue('id'), 324);
    }

    public function registeredColumnsAreDataToInsert(): void
    {
        $cmd = new Insert($this->db, 'table', new State(Node::NEW, []), null);

        Assert::false($cmd->hasData());

        $cmd->registerColumn('type', 'admin');

        Assert::true($cmd->hasData());
    }

    public function missingDatabaseGeneratedFieldIsDataToInsert(): void
    {
        $generated = ['updated_at' => GeneratedField::BEFORE_UPDATE, 'id' => GeneratedField::ON_INSERT];
        $cmd = new Insert($this->db, 'table', new State(Node::NEW, []), null, generated: $generated);

        Assert::true($cmd->hasData());
    }

    public function nullPrimaryKeyAndDatabaseGeneratedFieldsAreNotInserted(): void
    {
        $state = new State(Node::NEW, ['id' => null, 'name' => 'Antony', 'created_at' => null, 'updated_at' => null]);
        $generated = ['updated_at' => GeneratedField::BEFORE_UPDATE, 'created_at' => GeneratedField::ON_INSERT];
        $cmd = new Insert($this->db, 'table', $state, null, ['id'], generated: $generated);
        $query = m::mock(TestInsertCommand::class);
        $query->shouldReceive('values')->once()->with(['name' => 'Antony', 'updated_at' => null])->andReturnSelf();
        $query->allows('run')->andReturn(42);
        $this->db->allows('insert')->with('table')->andReturn($query);

        $cmd->execute();

        Assert::same($state->getValue('id'), 42);
        Assert::true($cmd->isExecuted());
    }

    public function severalReturnedFieldsAreCastAndRegistered(): void
    {
        $state = new State(Node::SCHEDULED_INSERT, ['name' => 'Antony']);
        $generated = ['id' => GeneratedField::ON_INSERT, 'created_at' => GeneratedField::ON_INSERT];
        $cmd = new Insert($this->db, 'table', $state, $this->mapper, ['id'], 'id', $generated);
        $this->mapper->allows('uncast')->andReturnArg(0);
        $this->mapper->allows('mapColumns')->andReturnArg(0);
        $this->mapper->shouldReceive('cast')
            ->once()
            ->with(['id' => '7', 'created_at' => '2020-01-01'])
            ->andReturn(['id' => 7, 'created_at' => 'casted']);
        $query = m::mock(TestInsertCommandWithReturning::class);
        $query->shouldReceive('values')->once()->with(['name' => 'Antony'])->andReturnSelf();
        $query->shouldReceive('returning')->once()->with('id', 'created_at')->andReturnSelf();
        $query->shouldReceive('run')->once()->andReturn(['id' => '7', 'created_at' => '2020-01-01']);
        $this->db->allows('insert')->with('table')->andReturn($query);

        $cmd->execute();

        Assert::same($state->getValue('id'), 7);
        Assert::same($state->getValue('created_at'), 'casted');
    }

    public function storeDataHoldsColumnsAndMappedData(): void
    {
        $this->cmd->registerColumn('type', 'admin');
        $this->cmd->registerAppendix('name', 'Antony');
        $this->mapper->shouldReceive('mapColumns')
            ->once()
            ->with(['foo' => 'bar', 'name' => 'Antony'])
            ->andReturn(['foo' => 'bar', 'user_name' => 'Antony']);

        Assert::same($this->cmd->getStoreData(), ['type' => 'admin', 'foo' => 'bar', 'user_name' => 'Antony']);
        Assert::same($this->state->getData(), ['foo' => 'bar', 'name' => 'Antony']);
    }

    public function storeDataWithoutMapperHoldsRawData(): void
    {
        $cmd = new Insert($this->db, 'table', $this->state, null);

        Assert::same($cmd->getStoreData(), ['foo' => 'bar']);
    }

    public function registeredValueGoesToTheState(): void
    {
        $this->cmd->register('id', 5);

        Assert::same($this->state->getValue('id'), 5);
    }

    public function databaseCanBeReplaced(): void
    {
        $this->cmd->setDatabase($db = m::mock(DatabaseInterface::class));

        Assert::same($this->cmd->getDatabase(), $db);
    }

    public function errorOfCommandWithoutAwaitedValuesIsEmpty(): void
    {
        Assert::same($this->cmd->__toError(), Insert::class . '()');
    }

    #[BeforeTest]
    protected function setUp(): void
    {
        $this->mapper = m::mock(MapperInterface::class);

        $this->cmd = new Insert(
            $this->db = m::mock(DatabaseInterface::class),
            'table',
            $this->state = new State(Node::SCHEDULED_INSERT, ['foo' => 'bar']),
            $this->mapper,
            ['id'],
            'foo_id',
        );
    }
}
