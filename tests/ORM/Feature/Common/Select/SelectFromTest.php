<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Feature\Common\Select;

use Cycle\ORM\Mapper\Mapper;
use Cycle\ORM\Relation;
use Cycle\ORM\Schema;
use Cycle\ORM\Select;
use Cycle\ORM\Tests\Fixtures\Comment;
use Cycle\ORM\Tests\Fixtures\User;
use Cycle\Testing\ORM\OrmTestCase;

class SelectFromTest extends OrmTestCase
{

    #[\Testo\Test]
    public function testFromOverridesSchemaTable(): void
    {
        $select = new Select($this->orm, User::class);
        $select->from('user_archive');

        \Testo\Assert::string($select->sqlStatement())->contains('user_archive');
    }

    #[\Testo\Test]
    public function testFromFetchesDataFromAnotherTable(): void
    {
        $select = new Select($this->orm, User::class);
        $result = $select->from('user_archive')->orderBy('id')->fetchAll();

        \Testo\Assert::count($result, 1);
        \Testo\Assert::instanceOf($result[0], User::class);
        \Testo\Assert::same($result[0]->email, 'archived@world.com');
    }

    #[\Testo\Test]
    public function testFromWithWhere(): void
    {
        $select = new Select($this->orm, User::class);
        $result = $select->from('user_archive')->where('id', 1)->fetchAll();

        \Testo\Assert::count($result, 1);
        \Testo\Assert::same($result[0]->id, 1);
        \Testo\Assert::same($result[0]->email, 'archived@world.com');
    }

    #[\Testo\Test]
    public function testFromDoesNotAffectOriginalTable(): void
    {
        $select = new Select($this->orm, User::class);
        $result = $select->orderBy('id')->fetchAll();

        \Testo\Assert::count($result, 2);
        \Testo\Assert::same($result[0]->email, 'hello@world.com');
        \Testo\Assert::same($result[1]->email, 'another@world.com');
    }

    #[\Testo\Test]
    public function testFromReturnsFluent(): void
    {
        $select = new Select($this->orm, User::class);
        $result = $select->from('user_archive');

        \Testo\Assert::same($result, $select);
    }

    #[\Testo\Test]
    public function testFromWithLoadRelation(): void
    {
        $select = new Select($this->orm, User::class);
        $result = $select
            ->from('user_archive')
            ->load('comments')
            ->orderBy('id')
            ->fetchAll();

        \Testo\Assert::count($result, 1);
        \Testo\Assert::same($result[0]->email, 'archived@world.com');
        // Comments are loaded from the regular 'comment' table via user_id FK
        \Testo\Assert::count($result[0]->comments, 2);
        \Testo\Assert::same($result[0]->comments[0]->message, 'msg 1');
        \Testo\Assert::same($result[0]->comments[1]->message, 'msg 2');
    }

    #[\Testo\Test]
    public function testFromPreservesAlias(): void
    {
        $select = new Select($this->orm, User::class);
        $sql = $select->from('user_archive')->sqlStatement();

        // The alias should remain the entity role ('user') so that column references still work
        \Testo\Assert::string($sql)->contains('user_archive')->notContains('"user"."user"');
    }

    #[\Testo\Test]
    public function testFromWithCount(): void
    {
        $select = new Select($this->orm, User::class);
        $count = $select->from('user_archive')->count();

        \Testo\Assert::same($count, 1);
    }

    #[\Testo\Lifecycle\BeforeTest]
    public function setUp(): void
    {
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
