<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Functional\Driver\Common\Select;

use Cycle\ORM\Mapper\Mapper;
use Cycle\ORM\Relation;
use Cycle\ORM\Schema;
use Cycle\ORM\Select;
use Cycle\ORM\Tests\Functional\Driver\Common\BaseTest;
use Cycle\ORM\Tests\Fixtures\Comment;
use Cycle\ORM\Tests\Fixtures\User;
use Cycle\ORM\Tests\Traits\TableTrait;

abstract class SelectFromTest extends BaseTest
{
    use TableTrait;

    public function testFromOverridesSchemaTable(): void
    {
        $select = new Select($this->orm, User::class);
        $select->from('user_archive');

        $this->assertStringContainsString('user_archive', $select->sqlStatement());
    }

    public function testFromFetchesDataFromAnotherTable(): void
    {
        $select = new Select($this->orm, User::class);
        $result = $select->from('user_archive')->orderBy('id')->fetchAll();

        $this->assertCount(1, $result);
        $this->assertInstanceOf(User::class, $result[0]);
        $this->assertSame('archived@world.com', $result[0]->email);
    }

    public function testFromWithWhere(): void
    {
        $select = new Select($this->orm, User::class);
        $result = $select->from('user_archive')->where('id', 1)->fetchAll();

        $this->assertCount(1, $result);
        $this->assertSame(1, $result[0]->id);
        $this->assertSame('archived@world.com', $result[0]->email);
    }

    public function testFromDoesNotAffectOriginalTable(): void
    {
        $select = new Select($this->orm, User::class);
        $result = $select->orderBy('id')->fetchAll();

        $this->assertCount(2, $result);
        $this->assertSame('hello@world.com', $result[0]->email);
        $this->assertSame('another@world.com', $result[1]->email);
    }

    public function testFromReturnsFluent(): void
    {
        $select = new Select($this->orm, User::class);
        $result = $select->from('user_archive');

        $this->assertSame($select, $result);
    }

    public function testFromWithLoadRelation(): void
    {
        $select = new Select($this->orm, User::class);
        $result = $select
            ->from('user_archive')
            ->load('comments')
            ->orderBy('id')
            ->fetchAll();

        $this->assertCount(1, $result);
        $this->assertSame('archived@world.com', $result[0]->email);
        // Comments are loaded from the regular 'comment' table via user_id FK
        $this->assertCount(2, $result[0]->comments);
        $this->assertSame('msg 1', $result[0]->comments[0]->message);
        $this->assertSame('msg 2', $result[0]->comments[1]->message);
    }

    public function testFromPreservesAlias(): void
    {
        $select = new Select($this->orm, User::class);
        $sql = $select->from('user_archive')->sqlStatement();

        // The alias should remain the entity role ('user') so that column references still work
        $this->assertStringContainsString('user_archive', $sql);
        $this->assertStringNotContainsString('"user"."user"', $sql);
    }

    public function testFromWithCount(): void
    {
        $select = new Select($this->orm, User::class);
        $count = $select->from('user_archive')->count();

        $this->assertSame(1, $count);
    }

    public function setUp(): void
    {
        parent::setUp();

        $this->makeTable('user', [
            'id_int' => 'primary',
            'email_str' => 'string',
            'balance_float' => 'float',
        ]);

        $this->makeTable('user_archive', [
            'id_int' => 'primary',
            'email_str' => 'string',
            'balance_float' => 'float',
        ]);

        $this->makeTable('comment', [
            'id_int' => 'primary',
            'user_id_int' => 'integer',
            'message_str' => 'string',
        ]);

        $this->getDatabase()->table('user')->insertMultiple(
            ['email_str', 'balance_float'],
            [
                ['hello@world.com', 100],
                ['another@world.com', 200],
            ],
        );

        $this->getDatabase()->table('user_archive')->insertMultiple(
            ['email_str', 'balance_float'],
            [
                ['archived@world.com', 50],
            ],
        );

        $this->getDatabase()->table('comment')->insertMultiple(
            ['user_id_int', 'message_str'],
            [
                [1, 'msg 1'],
                [1, 'msg 2'],
                [2, 'msg 3'],
            ],
        );

        $this->orm = $this->withSchema(new Schema([
            User::class => [
                Schema::ROLE => 'user',
                Schema::MAPPER => Mapper::class,
                Schema::DATABASE => 'default',
                Schema::TABLE => 'user',
                Schema::PRIMARY_KEY => 'id',
                Schema::COLUMNS => ['id' => 'id_int', 'email' => 'email_str', 'balance' => 'balance_float'],
                Schema::SCHEMA => [],
                Schema::TYPECAST => [
                    'id' => 'int',
                    'balance' => 'float',
                ],
                Schema::RELATIONS => [
                    'comments' => [
                        Relation::TYPE => Relation::HAS_MANY,
                        Relation::TARGET => Comment::class,
                        Relation::SCHEMA => [
                            Relation::CASCADE => true,
                            Relation::INNER_KEY => 'id',
                            Relation::OUTER_KEY => 'user_id',
                        ],
                    ],
                ],
            ],
            Comment::class => [
                Schema::ROLE => 'comment',
                Schema::MAPPER => Mapper::class,
                Schema::DATABASE => 'default',
                Schema::TABLE => 'comment',
                Schema::PRIMARY_KEY => 'id',
                Schema::COLUMNS => ['id' => 'id_int', 'user_id' => 'user_id_int', 'message' => 'message_str'],
                Schema::TYPECAST => [
                    'id' => 'int',
                    'user_id' => 'int',
                ],
                Schema::SCHEMA => [],
                Schema::RELATIONS => [],
            ],
        ]));
    }
}
