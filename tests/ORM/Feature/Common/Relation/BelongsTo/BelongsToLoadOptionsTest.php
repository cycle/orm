<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Feature\Common\Relation\BelongsTo;

use Cycle\ORM\Mapper\Mapper;
use Cycle\ORM\Relation;
use Cycle\ORM\Schema;
use Cycle\ORM\Select;
use Cycle\ORM\Select\Options\BelongsToLoadOptions;
use Cycle\ORM\Select\Options\LoadMethod;
use Cycle\ORM\Tests\Fixtures\Profile;
use Cycle\ORM\Tests\Fixtures\User;
use Cycle\ORM\Tests\Feature\Common\BaseTest;
use Cycle\ORM\Tests\Traits\TableTrait;

abstract class BelongsToLoadOptionsTest extends BaseTest
{
    use TableTrait;

    #[\Testo\Test]
    public function testLoadWithSingleQueryMethod(): void
    {
        $selector = new Select($this->orm, Profile::class);
        $selector->load('user', new BelongsToLoadOptions(
            method: LoadMethod::SingleQuery,
        ))->orderBy('profile.id');

        \Testo\Assert::equals($selector->fetchData(), [
            [
                'id' => 1,
                'user_id' => 1,
                'image' => 'image.png',
                'user' => [
                    'id' => 1,
                    'email' => 'hello@world.com',
                    'balance' => 100.0,
                ],
            ],
            [
                'id' => 2,
                'user_id' => 2,
                'image' => 'second.png',
                'user' => [
                    'id' => 2,
                    'email' => 'another@world.com',
                    'balance' => 200.0,
                ],
            ],
            [
                'id' => 3,
                'user_id' => 2,
                'image' => 'third.png',
                'user' => [
                    'id' => 2,
                    'email' => 'another@world.com',
                    'balance' => 200.0,
                ],
            ],
        ]);
    }

    #[\Testo\Test]
    public function testLoadWithOuterQueryMethod(): void
    {
        $selector = new Select($this->orm, Profile::class);
        $selector->load('user', new BelongsToLoadOptions(
            method: LoadMethod::OuterQuery,
        ))->orderBy('profile.id');

        \Testo\Assert::equals($selector->fetchData(), [
            [
                'id' => 1,
                'user_id' => 1,
                'image' => 'image.png',
                'user' => [
                    'id' => 1,
                    'email' => 'hello@world.com',
                    'balance' => 100.0,
                ],
            ],
            [
                'id' => 2,
                'user_id' => 2,
                'image' => 'second.png',
                'user' => [
                    'id' => 2,
                    'email' => 'another@world.com',
                    'balance' => 200.0,
                ],
            ],
            [
                'id' => 3,
                'user_id' => 2,
                'image' => 'third.png',
                'user' => [
                    'id' => 2,
                    'email' => 'another@world.com',
                    'balance' => 200.0,
                ],
            ],
        ]);
    }

    #[\Testo\Test]
    public function testLoadWithUsing(): void
    {
        $selector = new Select($this->orm, Profile::class);
        $selector
            ->with('user', ['as' => 'user'])
            ->load('user', new BelongsToLoadOptions(
                using: 'user',
            ))
            ->orderBy('user.id', 'DESC')
            ->orderBy('id', 'DESC')
            ->limit(1);

        \Testo\Assert::equals($selector->fetchData(), [
            [
                'id' => 3,
                'user_id' => 2,
                'image' => 'third.png',
                'user' => [
                    'id' => 2,
                    'email' => 'another@world.com',
                    'balance' => 200.0,
                ],
            ],
        ]);
    }

    #[\Testo\Test]
    public function testLoadWithDefaultOptions(): void
    {
        $selector = new Select($this->orm, Profile::class);
        $selector->load('user', new BelongsToLoadOptions())->orderBy('profile.id');

        $res = $selector->fetchAll();

        \Testo\Assert::count($res, 3);
        \Testo\Assert::same($res[0]->user->email, 'hello@world.com');
        \Testo\Assert::same($res[1]->user->email, 'another@world.com');
        \Testo\Assert::same($res[2]->user->email, 'another@world.com');
    }

    #[\Testo\Test]
    public function testLoadFromArchiveTable(): void
    {
        $this->makeTable('user_archive', [
            'id' => 'primary',
            'email' => 'string',
            'balance' => 'float',
        ]);

        $this->getDatabase()->table('user_archive')->insertMultiple(
            ['email', 'balance'],
            [
                ['archived@world.com', 999],
                ['archived2@world.com', 888],
            ],
        );

        $res = (new Select($this->orm, Profile::class))->load('user', new BelongsToLoadOptions(
            table: 'user_archive',
        ))->orderBy('profile.id')->fetchAll();

        \Testo\Assert::count($res, 3);
        \Testo\Assert::same($res[0]->user->email, 'archived@world.com');
        \Testo\Assert::same($res[1]->user->email, 'archived2@world.com');
        \Testo\Assert::same($res[2]->user->email, 'archived2@world.com');
    }

    #[\Testo\Lifecycle\BeforeTest]
    public function setUp(): void
    {
        parent::setUp();

        $this->makeTable('user', [
            'id' => 'primary',
            'email' => 'string',
            'balance' => 'float',
        ]);

        $this->getDatabase()->table('user')->insertMultiple(
            ['email', 'balance'],
            [
                ['hello@world.com', 100],
                ['another@world.com', 200],
            ],
        );

        $this->makeTable('profile', [
            'id' => 'primary',
            'user_id' => 'integer,nullable',
            'image' => 'string',
        ]);

        $this->getDatabase()->table('profile')->insertMultiple(
            ['user_id', 'image'],
            [
                [1, 'image.png'],
                [2, 'second.png'],
                [2, 'third.png'],
            ],
        );

        $this->makeFK('profile', 'user_id', 'user', 'id');

        $this->orm = $this->withSchema(new Schema([
            User::class => [
                Schema::ROLE => 'user',
                Schema::MAPPER => Mapper::class,
                Schema::DATABASE => 'default',
                Schema::TABLE => 'user',
                Schema::PRIMARY_KEY => 'id',
                Schema::COLUMNS => ['id', 'email', 'balance'],
                Schema::SCHEMA => [],
                Schema::RELATIONS => [],
            ],
            Profile::class => [
                Schema::ROLE => 'profile',
                Schema::MAPPER => Mapper::class,
                Schema::DATABASE => 'default',
                Schema::TABLE => 'profile',
                Schema::PRIMARY_KEY => 'id',
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
                        ],
                    ],
                ],
            ],
        ]));
    }
}
