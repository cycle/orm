<?php
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

namespace Spiral\ORM\Tests;

use Spiral\ORM\Relation;
use Spiral\ORM\Schema;
use Spiral\ORM\Selector;
use Spiral\ORM\Tests\Fixtures\Comment;
use Spiral\ORM\Tests\Fixtures\EntityMapper;
use Spiral\ORM\Tests\Fixtures\User;
use Spiral\ORM\Tests\Traits\TableTrait;
use Spiral\ORM\Transaction;

abstract class CyclicReferencesTest extends BaseTest
{
    use TableTrait;

    public function setUp()
    {
        parent::setUp();

        $this->makeTable('user', [
            'id'         => 'primary',
            'email'      => 'string',
            'balance'    => 'float',
            'comment_id' => 'integer,nullable'
        ]);

        $this->makeTable('comment', [
            'id'      => 'primary',
            'user_id' => 'integer',
            'message' => 'string'
        ], [
            'user_id' => ['table' => 'user', 'column' => 'id']
        ]);

        $this->makeTable('favorites_map', [
            'user_id'    => 'integer',
            'comment_id' => 'integer'
        ]);

        $this->orm = $this->orm->withSchema(new Schema([
            User::class    => [
                Schema::ALIAS       => 'user',
                Schema::MAPPER      => EntityMapper::class,
                Schema::DATABASE    => 'default',
                Schema::TABLE       => 'user',
                Schema::PRIMARY_KEY => 'id',
                Schema::COLUMNS     => ['id', 'email', 'balance', 'comment_id'],
                Schema::SCHEMA      => [],
                Schema::RELATIONS   => [
                    'lastComment' => [
                        Relation::TYPE   => Relation::REFERS_TO,
                        Relation::TARGET => Comment::class,
                        Relation::SCHEMA => [
                            Relation::CASCADE   => true,
                            Relation::INNER_KEY => 'comment_id',
                            Relation::OUTER_KEY => 'id',
                            Relation::NULLABLE  => true
                        ],
                    ],
                    'comments'    => [
                        Relation::TYPE   => Relation::HAS_MANY,
                        Relation::TARGET => Comment::class,
                        Relation::SCHEMA => [
                            Relation::CASCADE   => true,
                            Relation::INNER_KEY => 'id',
                            Relation::OUTER_KEY => 'user_id',
                        ],
                    ],
                    'favorites'   => [
                        Relation::TYPE   => Relation::MANY_TO_MANY,
                        Relation::TARGET => Comment::class,
                        Relation::SCHEMA => [
                            Relation::CASCADE           => true,
                            Relation::PIVOT_TABLE       => 'favorites_map',
                            Relation::PIVOT_DATABASE    => 'default',
                            Relation::PIVOT_COLUMNS     => ['user_id', 'comment_id'],
                            Relation::INNER_KEY         => 'id',
                            Relation::OUTER_KEY         => 'id',
                            Relation::THOUGHT_INNER_KEY => 'user_id',
                            Relation::THOUGHT_OUTER_KEY => 'comment_id',
                        ],
                    ],
                ]
            ],
            Comment::class => [
                Schema::ALIAS       => 'comment',
                Schema::MAPPER      => EntityMapper::class,
                Schema::DATABASE    => 'default',
                Schema::TABLE       => 'comment',
                Schema::PRIMARY_KEY => 'id',
                Schema::COLUMNS     => ['id', 'user_id', 'message'],
                Schema::SCHEMA      => [],
                Schema::RELATIONS   => [
                    'user'         => [
                        Relation::TYPE   => Relation::BELONGS_TO,
                        Relation::TARGET => User::class,
                        Relation::SCHEMA => [
                            Relation::CASCADE   => true,
                            Relation::INNER_KEY => 'user_id',
                            Relation::OUTER_KEY => 'id',
                        ],
                    ],
                    'favorited_by' => [
                        Relation::TYPE   => Relation::MANY_TO_MANY,
                        Relation::TARGET => User::class,
                        Relation::SCHEMA => [
                            Relation::CASCADE           => true,
                            Relation::PIVOT_TABLE       => 'favorites_map',
                            Relation::PIVOT_DATABASE    => 'default',
                            Relation::PIVOT_COLUMNS     => ['user_id', 'comment_id'],
                            Relation::INNER_KEY         => 'id',
                            Relation::OUTER_KEY         => 'id',
                            Relation::THOUGHT_INNER_KEY => 'comment_id',
                            Relation::THOUGHT_OUTER_KEY => 'user_id',
                        ],
                    ],
                ]
            ]
        ]));
    }

    public function testCreate()
    {
        $u = new User();
        $u->email = 'test@email.com';
        $u->balance = 1000;

        $c = new Comment();
        $c->user = $u;
        $c->message = 'hello world';

        $u->addComment($c);
        $u->favorites->add($c);

        $this->captureWriteQueries();

        $tr = new Transaction($this->orm);
        $tr->store($u);
        $tr->run();

        $this->assertNumWrites(4);

        // no changes!
        $this->captureWriteQueries();
        $tr = new Transaction($this->orm);
        $tr->store($u);
        $tr->run();
        $this->assertNumWrites(0);

        $selector = new Selector($this->orm, User::class);
        $selector->load('lastComment.user')
            ->load('comments.user')
            ->load('comments.favorited_by')
            ->load('favorites');

        $u1 = $selector->fetchOne();

        $this->assertEquals($u->id, $u1->id);
        $this->assertEquals($u->lastComment->id, $u1->lastComment->id);

        $this->assertEquals($u->lastComment->user->id, $u1->lastComment->user->id);
        $this->assertEquals($u->comments[0]->id, $u1->comments[0]->id);
        $this->assertEquals($u->favorites[0]->id, $u1->favorites[0]->id);
        $this->assertEquals($u->favorites[0]->user->id, $u1->favorites[0]->user->id);
        $this->assertEquals($u->id, $u1->favorites[0]->favorited_by[0]->id);
    }
}