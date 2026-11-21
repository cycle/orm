<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Unit\Mapper;

use Cycle\ORM\Mapper\Mapper;
use Cycle\ORM\MapperInterface;
use Cycle\ORM\Schema;
use Cycle\ORM\Tests\Fixtures\Admin;
use Cycle\ORM\Tests\Fixtures\Post;
use Cycle\ORM\Tests\Fixtures\User;
use Cycle\Testing\ORM\Memory;
use Testo\Assert;
use Testo\Expect;
use Testo\Test;

#[Test]
final class SingleTableInheritanceTest
{
    public function childIsMadeByItsRole(): void
    {
        Assert::instanceOf($this->mapper()->init([], 'admin'), Admin::class);
    }

    public function roleOutsideTheHierarchyIsRejected(): never
    {
        $mapper = $this->mapper();

        Expect::exception(\InvalidArgumentException::class)->withMessage('Role `user` does not have a child role `post`.');

        $mapper->init([], 'post');
    }

    private function mapper(): MapperInterface
    {
        $orm = Memory::orm(new Schema([
            'user' => [
                Schema::ENTITY => User::class,
                Schema::CHILDREN => ['admin' => Admin::class],
                Schema::MAPPER => Mapper::class,
                Schema::DATABASE => 'default',
                Schema::TABLE => 'user',
                Schema::PRIMARY_KEY => 'id',
                Schema::COLUMNS => ['id', 'email', '_type'],
                Schema::SCHEMA => [],
                Schema::RELATIONS => [],
            ],
            'admin' => [Schema::ENTITY => Admin::class],
            'post' => [
                Schema::ENTITY => Post::class,
                Schema::MAPPER => Mapper::class,
                Schema::DATABASE => 'default',
                Schema::TABLE => 'post',
                Schema::PRIMARY_KEY => 'id',
                Schema::COLUMNS => ['id'],
                Schema::SCHEMA => [],
                Schema::RELATIONS => [],
            ],
        ]));

        return $orm->getMapper('user');
    }
}
