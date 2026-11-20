<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Unit\Mapper;

use Cycle\ORM\Command\Database\Delete;
use Cycle\ORM\Command\Database\Insert;
use Cycle\ORM\Command\Database\Update;
use Cycle\ORM\Heap\Node;
use Cycle\ORM\Heap\State;
use Cycle\ORM\Mapper\Mapper;
use Cycle\ORM\ORMInterface;
use Cycle\ORM\Schema;
use Cycle\ORM\Tests\Fixtures\User;
use Cycle\Testing\ORM\Memory;
use Testo\Assert;
use Testo\Test;

#[Test]
final class DatabaseMapperTest
{
    public function insertStoresMappedColumnsInEntityTable(): void
    {
        $orm = $this->orm();
        $state = new State(Node::NEW, ['email' => 'hello@world.com', 'balance' => 10.0, 'profile' => null]);

        $command = $orm->getMapper('user')->queueCreate(new User(), new Node(Node::NEW, [], 'user'), $state);

        Assert::instanceOf($command, Insert::class);
        Assert::same($command->getDatabase(), $orm->getSource('user')->getDatabase());
        Assert::same($state->getStatus(), Node::SCHEDULED_INSERT);
        Assert::same($command->getStoreData(), ['email_address' => 'hello@world.com', 'balance' => 10.0]);
    }

    public function updateIsScopedByStoredPrimaryKey(): void
    {
        $orm = $this->orm();
        $state = new State(Node::MANAGED, ['id' => 1, 'email' => 'old@world.com']);
        $state->register('email', 'new@world.com');

        $command = $orm->getMapper('user')->queueUpdate(new User(), new Node(Node::MANAGED, ['id' => 1], 'user'), $state);

        Assert::instanceOf($command, Update::class);
        Assert::true($command->isReady());
        Assert::same($command->getScope(), ['id' => 1]);
        Assert::same($command->getStoreData(), ['email_address' => 'new@world.com']);
    }

    public function deleteIsScopedByNodePrimaryKey(): void
    {
        $orm = $this->orm();
        $state = new State(Node::MANAGED, ['id' => 3]);

        $command = $orm->getMapper('user')->queueDelete(new User(), new Node(Node::MANAGED, ['id' => 3], 'user'), $state);

        Assert::instanceOf($command, Delete::class);
        Assert::true($command->isReady());
        Assert::same($command->getScope(), ['id' => 3]);
        Assert::same($state->getStatus(), Node::SCHEDULED_DELETE);
    }

    private function orm(): ORMInterface
    {
        return Memory::orm(new Schema([
            'user' => [
                Schema::ENTITY => User::class,
                Schema::MAPPER => Mapper::class,
                Schema::DATABASE => 'default',
                Schema::TABLE => 'user',
                Schema::PRIMARY_KEY => 'id',
                Schema::COLUMNS => ['id', 'email' => 'email_address', 'balance'],
                Schema::SCHEMA => [],
                Schema::RELATIONS => [],
            ],
        ]));
    }
}
