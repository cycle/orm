<?php
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

namespace Spiral\Cycle\Tests;

use Spiral\Cycle\Mapper\Mapper;
use Spiral\Cycle\Selector\JoinableLoader;
use Spiral\Cycle\Relation;
use Spiral\Cycle\Schema;
use Spiral\Cycle\Selector;
use Spiral\Cycle\Tests\Fixtures\Comment;
use Spiral\Cycle\Tests\Fixtures\User;
use Spiral\Cycle\Tests\Traits\TableTrait;

abstract class SelectorTest extends BaseTest
{
    use TableTrait;

    public function setUp()
    {
        parent::setUp();

        $this->makeTable('user', [
            'id'      => 'primary',
            'email'   => 'string',
            'balance' => 'float'
        ]);

        $this->getDatabase()->table('user')->insertMultiple(
            ['email', 'balance'],
            [
                ['hello@world.com', 100],
                ['another@world.com', 200],
            ]
        );

        $this->makeTable('comment', [
            'id'      => 'primary',
            'user_id' => 'integer',
            'level'   => 'integer',
            'message' => 'string'
        ]);

        $this->getDatabase()->table('comment')->insertMultiple(
            ['user_id', 'level', 'message'],
            [
                [1, 1, 'msg 1'],
                [1, 2, 'msg 2'],
                [1, 3, 'msg 3'],
                [1, 4, 'msg 4'],
                [2, 1, 'msg 2.1'],
                [2, 2, 'msg 2.2'],
                [2, 3, 'msg 2.3'],
            ]
        );

        $this->orm = $this->withSchema(new Schema([
            User::class    => [
                Schema::ALIAS       => 'user',
                Schema::MAPPER      => Mapper::class,
                Schema::DATABASE    => 'default',
                Schema::TABLE       => 'user',
                Schema::PRIMARY_KEY => 'id',
                Schema::COLUMNS     => ['id', 'email', 'balance'],
                Schema::SCHEMA      => [],
                Schema::RELATIONS   => [
                    'comments' => [
                        Relation::TYPE   => Relation::HAS_MANY,
                        Relation::TARGET => Comment::class,
                        Relation::SCHEMA => [
                            Relation::CASCADE   => true,
                            Relation::INNER_KEY => 'id',
                            Relation::OUTER_KEY => 'user_id',
                        ],
                    ]
                ]
            ],
            Comment::class => [
                Schema::ALIAS       => 'comment',
                Schema::MAPPER      => Mapper::class,
                Schema::DATABASE    => 'default',
                Schema::TABLE       => 'comment',
                Schema::PRIMARY_KEY => 'id',
                Schema::COLUMNS     => ['id', 'user_id', 'level', 'message'],
                Schema::SCHEMA      => [],
                Schema::RELATIONS   => []
            ]
        ]));
    }

    public function testStableStatement()
    {
        $s = new Selector($this->orm, User::class);
        $s->load('comments', ['method' => JoinableLoader::INLOAD]);


//        $this->assertSQL('SELECT
//"user"."id" AS "c0", "user"."email" AS "c1", "user"."balance" AS "c2", "d123"."id" AS "c3", "d123"."user_id" AS "c4", "d123"."level" AS "c5", "d123"."message" AS "c6"
//FROM "user" AS "user"
//LEFT JOIN "comment" AS "d123"
//    ON "d123"."user_id" = "user"."id"', $s->sqlStatement());

        $s2 = clone $s;
        $this->assertSQL($s->sqlStatement(), $s2->sqlStatement());
    }
}