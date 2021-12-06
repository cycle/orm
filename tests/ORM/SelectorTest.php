<?php

/**
 * Cycle DataMapper ORM
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

declare(strict_types=1);

namespace Cycle\ORM\Tests;

use Cycle\ORM\Mapper\Mapper;
use Cycle\ORM\Relation;
use Cycle\ORM\Schema;
use Cycle\ORM\Select;
use Cycle\ORM\Select\JoinableLoader;
use Cycle\ORM\Select\RootLoader;
use Cycle\ORM\Tests\Fixtures\Comment;
use Cycle\ORM\Tests\Fixtures\Profile;
use Cycle\ORM\Tests\Fixtures\User;
use Cycle\ORM\Tests\Traits\TableTrait;

abstract class SelectorTest extends BaseTest
{
    use TableTrait;

    public function setUp(): void
    {
        parent::setUp();

        $this->makeTable('user', [
            'id' => 'primary',
            'email' => 'string',
            'balance' => 'float',
            'comment_id' => 'integer,nullable',
        ]);

        $this->getDatabase()->table('user')->insertMultiple(
            ['email', 'balance'],
            [
                ['hello@world.com', 100],
                ['another@world.com', 200],
            ]
        );

        $this->makeTable('profile', [
            'id' => 'primary',
            'user_id' => 'integer',
            'image' => 'string',
        ]);

        $this->getDatabase()->table('profile')->insertMultiple(
            ['user_id', 'image'],
            [
                [1, 'image.png'],
                [2, 'second.png'],
            ]
        );

        $this->makeTable('comment', [
            'id' => 'primary',
            'user_id' => 'integer',
            'level' => 'integer',
            'message' => 'string',
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

        $this->orm = $this->withSchema(new Schema($this->getSchemaDefinition()));
    }

    private function getSchemaDefinition(): array
    {
        return [
            User::class => [
                Schema::ROLE => 'user',
                Schema::MAPPER => Mapper::class,
                Schema::DATABASE => 'default',
                Schema::TABLE => 'user',
                Schema::PRIMARY_KEY => 'id',
                Schema::COLUMNS => ['id', 'email', 'balance', 'comment_id'],
                Schema::SCHEMA => [],
                Schema::RELATIONS => [
                    'profile' => [
                        Relation::TYPE => Relation::HAS_ONE,
                        Relation::TARGET => Profile::class,
                        Relation::SCHEMA => [
                            Relation::CASCADE => true,
                            Relation::INNER_KEY => 'id',
                            Relation::OUTER_KEY => 'user_id',
                            Relation::NULLABLE => false,
                        ],
                    ],
                    'lastComment' => [
                        Relation::TYPE => Relation::REFERS_TO,
                        Relation::TARGET => Comment::class,
                        Relation::SCHEMA => [
                            Relation::CASCADE => true,
                            Relation::INNER_KEY => 'comment_id',
                            Relation::OUTER_KEY => 'id',
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
                Schema::COLUMNS => ['id', 'user_id', 'level', 'message'],
                Schema::SCHEMA => [],
                Schema::RELATIONS => [],
            ],
            Profile::class => [
                Schema::ROLE => 'profile',
                Schema::MAPPER => Mapper::class,
                Schema::DATABASE => 'default',
                Schema::TABLE => 'profile',
                Schema::PRIMARY_KEY => 'id',
                Schema::FIND_BY_KEYS => ['user_id'],
                Schema::COLUMNS => ['id', 'user_id', 'image'],
                Schema::SCHEMA => [],
                Schema::RELATIONS => [
                    'user' => [
                        Relation::TYPE => Relation::BELONGS_TO,
                        Relation::TARGET => User::class,
                        Relation::SCHEMA => [
                            Relation::CASCADE => true,
                            Relation::INNER_KEY => 'user_id',
                            Relation::OUTER_KEY => 'id',
                            Relation::NULLABLE => false,
                        ],
                    ],
                ],
            ],
        ];
    }

    public function testStableStatement(): void
    {
        $s = new Select($this->orm, User::class);
        $s->load('comments', ['method' => JoinableLoader::INLOAD]);

        $s2 = new Select($this->orm, User::class);
        $s2->load('comments', ['method' => JoinableLoader::INLOAD]);

        $this->assertSQL($s->sqlStatement(), $s2->sqlStatement());
    }

    public function testSelectCustomSQL(): void
    {
        $s = new Select($this->orm, User::class);
        $s->with('comments', ['method' => JoinableLoader::INLOAD]);

        $query = $s->buildQuery()->columns(
            'user.id',
            'SUM(user.balance) as balance',
            'COUNT(user_comments.id) as count_comments'
        )->groupBy('user.id')->orderBy('user.id');

        $result = $query->fetchAll();

        $this->assertEquals([
            [
                'id' => 1,
                'balance' => 400.0,
                'count_comments' => 4,
            ],
            [
                'id' => 2,
                'balance' => 600.0,
                'count_comments' => 3,
            ],
        ], $result);
    }

    public function testCount(): void
    {
        $this->assertSame(2, (new Select($this->orm, User::class))->count());
        $this->assertSame(2, (new Select($this->orm, User::class))->with('profile')->count());
        $this->assertSame(2, (new Select($this->orm, User::class))->with('lastComment', [
            'method' => Select\AbstractLoader::LEFT_JOIN,
        ])->count());
        $this->assertSame(2, (new Select($this->orm, User::class))->with('comments')->count());

        $schema = $this->getSchemaDefinition();
        unset($schema[Profile::class][Schema::FIND_BY_KEYS]);
        $this->orm = $this->withSchema(new Schema($schema));
        $this->assertSame(2, (new Select($this->orm, User::class))->with('profile')->count());
    }

    public function testCountField(): void
    {
        $role = $this->orm->resolveRole(User::class);
        $pk = \sprintf('%s.%s', $role, 'id');
        $distinct = \sprintf('DISTINCT(%s)', $pk);

        $this->assertSame('*', (new RootLoader($this->orm, $role))->getCountField());
        $this->assertSame('*', $this->joinRelation(new RootLoader($this->orm, $role), 'profile')->getCountField());
        $this->assertSame('*', $this->joinRelation(new RootLoader($this->orm, $role), 'lastComment', [
            'method' => Select\AbstractLoader::LEFT_JOIN,
        ])->getCountField());
        $this->assertSame(
            $distinct,
            $this->joinRelation(new RootLoader($this->orm, $role), 'comments')->getCountField()
        );

        $schema = $this->getSchemaDefinition();
        unset($schema[Profile::class][Schema::FIND_BY_KEYS]);
        $this->orm = $this->withSchema(new Schema($schema));
        $this->assertSame(
            $distinct,
            $this->joinRelation(new RootLoader($this->orm, $role), 'profile')->getCountField()
        );
    }

    private function joinRelation(RootLoader $loader, string $relation, array $options = []): RootLoader
    {
        $loader->loadRelation($relation, $options, true);
        return $loader;
    }
}
