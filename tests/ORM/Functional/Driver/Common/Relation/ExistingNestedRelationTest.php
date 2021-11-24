<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Functional\Driver\Common\Relation;

use Cycle\ORM\Heap\Heap;
use Cycle\ORM\Mapper\Mapper;
use Cycle\ORM\Relation;
use Cycle\ORM\Schema;
use Cycle\ORM\Select;
use Cycle\ORM\Tests\Fixtures\Image;
use Cycle\ORM\Tests\Functional\Driver\Common\BaseTest;
use Cycle\ORM\Tests\Fixtures\Comment;
use Cycle\ORM\Tests\Fixtures\User;
use Cycle\ORM\Tests\Traits\TableTrait;
use Cycle\ORM\Transaction;

abstract class ExistingNestedRelationTest extends BaseTest
{
    use TableTrait;

    public function setUp(): void
    {
        parent::setUp();

        $this->makeTable('user', [
            'id' => 'primary',
            'email' => 'string',
            'balance' => 'float',
            'image_id' => 'integer,nullable',
        ]);

        $this->makeTable('image', [
            'id' => 'primary',
            'parent' => 'string',
            'url' => 'string',
        ]);

        $this->makeTable('comment', [
            'id' => 'primary',
            'user_id' => 'integer',
            'message' => 'string',
        ], [
            'user_id' => ['table' => 'user', 'column' => 'id'],
        ]);

        $this->makeFK('user', 'image_id', 'image', 'id');
        $this->makeFK('comment', 'user_id', 'user', 'id');

        $this->orm = $this->withSchema(new Schema([
            Image::class => [
                Schema::ROLE => 'image',
                Schema::MAPPER => Mapper::class,
                Schema::DATABASE => 'default',
                Schema::TABLE => 'image',
                Schema::PRIMARY_KEY => 'id',
                Schema::COLUMNS => ['id', 'parent', 'url'],
                Schema::SCHEMA => [],
                Schema::RELATIONS => [],
            ],
            User::class => [
                Schema::ROLE => 'user',
                Schema::MAPPER => Mapper::class,
                Schema::DATABASE => 'default',
                Schema::TABLE => 'user',
                Schema::PRIMARY_KEY => 'id',
                Schema::COLUMNS => ['id', 'email', 'balance', 'image_id'],
                Schema::SCHEMA => [],
                Schema::RELATIONS => [
                    'image' => [
                        Relation::TYPE => Relation::BELONGS_TO,
                        Relation::TARGET => Image::class,
                        Relation::LOAD => Relation::LOAD_PROMISE,
                        Relation::SCHEMA => [
                            Relation::CASCADE => true,
                            Relation::INNER_KEY => 'image_id',
                            Relation::OUTER_KEY => 'id',
                            Relation::NULLABLE => true,
                        ],
                    ],
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
                Schema::COLUMNS => ['id', 'user_id', 'message'],
                Schema::SCHEMA => [],
                Schema::RELATIONS => [
                    'user' => [
                        Relation::TYPE => Relation::BELONGS_TO,
                        Relation::TARGET => User::class,
                        Relation::SCHEMA => [
                            Relation::CASCADE => true,
                            Relation::INNER_KEY => 'user_id',
                            Relation::OUTER_KEY => 'id',
                        ],
                    ],
                ],
            ],
        ]));
    }

    public function testCreateForExisting(): void
    {
        $u = new User();
        $u->email = 'test@email.com';
        $u->balance = 1000;

        $this->captureWriteQueries();

        $tr = new Transaction($this->orm);
        $tr->persist($u);
        $tr->run();

        $this->assertNumWrites(1);

        $this->orm = $this->orm->withHeap(new Heap());

        $selector = new Select($this->orm, User::class);
        /** @var User $u */
        $u = $selector->wherePK($u->id)->fetchOne();

        $c = new Comment();
        $c->user = $u;
        $c->message = 'hello world';

        $u->addComment($c);

        $this->captureWriteQueries();
        $tr = new Transaction($this->orm);
        $tr->persist($u);
        $tr->run();
        $this->assertNumWrites(1);
    }
}
