<?php
/**
 * Cycle DataMapper ORM
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */
declare(strict_types=1);

namespace Cycle\ORM\Tests\Classless;

use Cycle\ORM\Heap\Heap;
use Cycle\ORM\Mapper\StdMapper;
use Cycle\ORM\Relation;
use Cycle\ORM\Schema;
use Cycle\ORM\Select;
use Cycle\ORM\Tests\BaseTest;
use Cycle\ORM\Tests\Traits\TableTrait;
use Cycle\ORM\Transaction;
use Spiral\Database\ForeignKeyInterface;

abstract class ClasslessCyclicReferencesTest extends BaseTest
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

        $this->makeTable('favorites', [
            'id'         => 'primary',
            'user_id'    => 'integer',
            'comment_id' => 'integer'
        ]);

        $this->makeFK('comment', 'user_id', 'user', 'id');
        $this->makeFK(
            'favorites',
            'user_id',
            'user',
            'id',
            ForeignKeyInterface::NO_ACTION,
            ForeignKeyInterface::NO_ACTION
        );
        $this->makeFK(
            'favorites',
            'comment_id',
            'comment',
            'id',
            ForeignKeyInterface::NO_ACTION,
            ForeignKeyInterface::NO_ACTION
        );
        $this->orm = $this->withSchema(new Schema([
            'user'     => [
                Schema::MAPPER      => StdMapper::class,
                Schema::DATABASE    => 'default',
                Schema::TABLE       => 'user',
                Schema::PRIMARY_KEY => 'id',
                Schema::COLUMNS     => ['id', 'email', 'balance', 'comment_id'],
                Schema::SCHEMA      => [],
                Schema::RELATIONS   => [
                    'lastComment' => [
                        Relation::TYPE   => Relation::REFERS_TO,
                        Relation::TARGET => 'comment',
                        Relation::SCHEMA => [
                            Relation::CASCADE   => true,
                            Relation::INNER_KEY => 'comment_id',
                            Relation::OUTER_KEY => 'id',
                            Relation::NULLABLE  => true
                        ],
                    ],
                    'comments'    => [
                        Relation::TYPE   => Relation::HAS_MANY,
                        Relation::TARGET => 'comment',
                        Relation::SCHEMA => [
                            Relation::CASCADE   => true,
                            Relation::INNER_KEY => 'id',
                            Relation::OUTER_KEY => 'user_id',
                        ],
                    ],
                    'favorites'   => [
                        Relation::TYPE   => Relation::MANY_TO_MANY,
                        Relation::TARGET => 'comment',
                        Relation::SCHEMA => [
                            Relation::CASCADE          => true,
                            Relation::THOUGH_ENTITY    => 'favorite',
                            Relation::INNER_KEY        => 'id',
                            Relation::OUTER_KEY        => 'id',
                            Relation::THOUGH_INNER_KEY => 'user_id',
                            Relation::THOUGH_OUTER_KEY => 'comment_id',
                        ],
                    ],
                ]
            ],
            'comment'  => [
                Schema::MAPPER      => StdMapper::class,
                Schema::DATABASE    => 'default',
                Schema::TABLE       => 'comment',
                Schema::PRIMARY_KEY => 'id',
                Schema::COLUMNS     => ['id', 'user_id', 'message'],
                Schema::SCHEMA      => [],
                Schema::RELATIONS   => [
                    'user'      => [
                        Relation::TYPE   => Relation::BELONGS_TO,
                        Relation::TARGET => 'user',
                        Relation::SCHEMA => [
                            Relation::CASCADE   => true,
                            Relation::INNER_KEY => 'user_id',
                            Relation::OUTER_KEY => 'id',
                        ],
                    ],
                    'favoredBy' => [
                        Relation::TYPE   => Relation::MANY_TO_MANY,
                        Relation::TARGET => 'user',
                        Relation::SCHEMA => [
                            Relation::CASCADE          => true,
                            Relation::THOUGH_ENTITY    => 'favorite',
                            Relation::INNER_KEY        => 'id',
                            Relation::OUTER_KEY        => 'id',
                            Relation::THOUGH_INNER_KEY => 'comment_id',
                            Relation::THOUGH_OUTER_KEY => 'user_id',
                        ],
                    ],
                ]
            ],
            'favorite' => [
                Schema::MAPPER      => StdMapper::class,
                Schema::DATABASE    => 'default',
                Schema::TABLE       => 'favorites',
                Schema::PRIMARY_KEY => 'id',
                Schema::COLUMNS     => ['id', 'user_id', 'comment_id'],
                Schema::SCHEMA      => [],
                Schema::RELATIONS   => []
            ]
        ]));
    }

    public function testCreate()
    {
        $u = $this->orm->make('user');
        $u->email = 'test@email.com';
        $u->balance = 1000;

        $c = $this->orm->make('comment');
        $c->user = $u;
        $c->message = 'hello world';

        $u->lastComment = $c;
        $u->comments[] = $c;
        $u->favorites->add($c);

        $this->captureWriteQueries();

        $tr = new Transaction($this->orm);
        $tr->persist($u);
        $tr->run();

        $this->assertNumWrites(4);

        // no changes!
        $this->captureWriteQueries();
        $tr = new Transaction($this->orm);
        $tr->persist($u);
        $tr->run();
        $this->assertNumWrites(0);

        $this->orm = $this->orm->withHeap(new Heap());
        $selector = new Select($this->orm, 'user');
        $selector->load('lastComment.user')
                 ->load('comments.user')
                 ->load('comments.favoredBy')
                 ->load('favorites');

        $u1 = $selector->wherePK(1)->fetchOne();

        $this->assertEquals($u->id, $u1->id);
        $this->assertEquals($u->lastComment->id, $u1->lastComment->id);

        $this->assertEquals($u->lastComment->user->id, $u1->lastComment->user->id);
        $this->assertEquals($u->comments[0]->id, $u1->comments[0]->id);
        $this->assertEquals($u->favorites[0]->id, $u1->favorites[0]->id);
        $this->assertEquals($u->favorites[0]->user->id, $u1->favorites[0]->user->id);
        $this->assertEquals($u->id, $u1->favorites[0]->favoredBy[0]->id);
    }

    public function testCreateMultipleLinkedTrees()
    {
        $u = $this->orm->make('user');
        $u->email = 'test@email.com';
        $u->balance = 1000;

        $u2 = $this->orm->make('user');
        $u2->email = 'u2@email.com';
        $u2->balance = 1000;

        $c = $this->orm->make('comment');
        $c->user = $u;
        $c->message = 'hello world';

        $u->lastComment = $c;
        $u->comments[] = $c;
        $u->favorites->add($c);
        $u2->favorites->add($c);

        $this->captureWriteQueries();

        $tr = new Transaction($this->orm);
        $tr->persist($u);
        $tr->persist($u2);
        $tr->run();

        $this->assertNumWrites(6);

        // no changes!
        $this->captureWriteQueries();
        $tr = new Transaction($this->orm);
        $tr->persist($u);
        $tr->persist($u2);
        $tr->run();
        $this->assertNumWrites(0);

        $this->orm = $this->orm->withHeap(new Heap());
        $selector = new Select($this->orm, 'user');
        $selector->load('lastComment.user')
                 ->load('comments.user')
                 ->load('comments.favoredBy')
                 ->load('favorites');

        $u1 = $selector->wherePK($u->id)->fetchOne();

        $this->assertEquals($u->id, $u1->id);
        $this->assertEquals($u->lastComment->id, $u1->lastComment->id);

        $this->assertEquals($u->lastComment->user->id, $u1->lastComment->user->id);
        $this->assertEquals($u->comments[0]->id, $u1->comments[0]->id);
        $this->assertEquals($u->favorites[0]->id, $u1->favorites[0]->id);
        $this->assertEquals($u->favorites[0]->user->id, $u1->favorites[0]->user->id);

        $fav = [
            $u1->favorites[0]->favoredBy[0]->id,
            $u1->favorites[0]->favoredBy[1]->id
        ];

        $this->assertCount(2, $fav);
        $this->assertContains($u->id, $fav);
        $this->assertContains($u2->id, $fav);

        $this->orm = $this->orm->withHeap(new Heap());
        $selector = new Select($this->orm, 'user');
        $selector->load('lastComment.user')
                 ->load('comments.user')
                 ->load('comments.favoredBy')
                 ->load('favorites');

        $u1 = $selector->wherePK(2)->fetchOne();

        $this->assertEquals($u1->id, $u2->id);
        $this->assertEquals($u1->favorites[0]->id, $u2->favorites[0]->id);
    }

    public function testCreateMultipleLinkedTreesExchange()
    {
        $u = $this->orm->make('user');
        $u->email = 'u1@email.com';
        $u->balance = 1000;

        $u2 = $this->orm->make('user');
        $u2->email = 'u2@email.com';
        $u2->balance = 1000;

        $c = $this->orm->make('comment');
        $c->user = $u;
        $c->message = 'hello u1';

        $c2 = $this->orm->make('comment');
        $c2->user = $u2;
        $c2->message = 'hello u2';

        $u->lastComment = $c;
        $u->comments[] = $c;

        $u2->lastComment = $c2;
        $u2->comments[] = $c2;

        $u->favorites->add($c2);
        $u2->favorites->add($c);
        $u->favorites->add($c);
        $u2->favorites->add($c2);

        $this->captureWriteQueries();

        $tr = new Transaction($this->orm);
        $tr->persist($u);
        $tr->persist($u2);
        $tr->run();

        $this->assertNumWrites(10);

        // no changes!
        $this->captureWriteQueries();
        $tr = new Transaction($this->orm);
        $tr->persist($u);
        $tr->persist($u2);
        $tr->run();
        $this->assertNumWrites(0);
    }
}
