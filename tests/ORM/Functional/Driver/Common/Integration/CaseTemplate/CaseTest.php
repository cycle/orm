<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Functional\Driver\Common\Integration\CaseTemplate;

use Cycle\ORM\Select;
use Cycle\ORM\Tests\Functional\Driver\Common\BaseTest;
use Cycle\ORM\Tests\Functional\Driver\Common\Integration\IntegrationTestTrait;
use Cycle\ORM\Tests\Traits\TableTrait;

abstract class CaseTest extends BaseTest
{
    use IntegrationTestTrait;
    use TableTrait;

    public function setUp(): void
    {
        // Init DB
        parent::setUp();
        $this->makeTables();
        $this->fillData();

        $this->loadSchema(__DIR__ . '/schema.php');
    }

    public function testSelect(): void
    {
        // Get entity
        $user = (new Select($this->orm, Entity\User::class))
            ->load('posts')
            ->wherePK(2)
            ->fetchOne();

        // Check result
        $this->assertInstanceOf(Entity\User::class, $user);
        $this->assertCount(2, $user->posts);
    }

    public function testSave(): void
    {
        // Get entity
        $user = (new Select($this->orm, Entity\User::class))
            ->wherePK(2)
            ->fetchOne();
        // Change data
        $user->setPassword('new-password-42');

        // Store changes and calc write queries
        $this->captureWriteQueries();
        $this->save($user);

        // Check write queries count
        $this->assertNumWrites(1);
    }

    private function makeTables(): void
    {
        // Make tables
        $this->makeTable(Entity\User::ROLE, [
            'id' => 'primary', // autoincrement
            'login' => 'string',
            'password_hash' => 'string',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ]);

        $this->makeTable('post', [
            'id' => 'primary',
            'user_id' => 'int',
            'slug' => 'string',
            'title' => 'string',
            'public' => 'bool',
            'content' => 'string',
            'published_at' => 'datetime,nullable',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
            'deleted_at' => 'datetime,nullable',
        ]);
        $this->makeFK('post', 'user_id', 'user', 'id', 'NO ACTION', 'NO ACTION');

        $this->makeTable('comment', [
            'id' => 'primary',
            'public' => 'bool',
            'content' => 'string',
            'user_id' => 'int',
            'post_id' => 'int',
            'published_at' => 'datetime,nullable',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
            'deleted_at' => 'datetime,nullable',
        ]);
        $this->makeFK('comment', 'user_id', 'user', 'id', 'NO ACTION', 'NO ACTION');
        $this->makeFK('comment', 'post_id', 'post', 'id', 'NO ACTION', 'NO ACTION');

        $this->makeTable('tag', [
            'id' => 'primary',
            'label' => 'string',
        ]);

        $this->makeTable('post_tag', [
            'post_id' => 'int',
            'tag_id' => 'int',
        ], pk: ['post_id', 'tag_id']);
        $this->makeFK('post_tag', 'post_id', 'post', 'id', 'NO ACTION', 'CASCADE');
        $this->makeFK('post_tag', 'tag_id', 'tag', 'id', 'NO ACTION', 'CASCADE');
    }

    private function fillData(): void
    {
        $this->getDatabase()->table('user')->insertMultiple(
            ['login', 'password_hash'],
            [
                ['user-1', '123456789'],
                ['user-2', '852741963'],
                ['user-3', '321654987'],
                ['user-4', '321456987'],
            ],
        );
        $this->getDatabase()->table('post')->insertMultiple(
            ['user_id', 'slug', 'title', 'public', 'content'],
            [
                [1, 'slug-string-1', 'Title 1', true, 'Foo-bar-baz content 1'],
                [2, 'slug-string-2', 'Title 2', true, 'Foo-bar-baz content 2'],
                [2, 'slug-string-3', 'Title 3', true, 'Foo-bar-baz content 3'],
                [3, 'slug-string-4', 'Title 4', true, 'Foo-bar-baz content 4'],
                [3, 'slug-string-5', 'Title 5', true, 'Foo-bar-baz content 5'],
                [3, 'slug-string-6', 'Title 6', true, 'Foo-bar-baz content 6'],
            ],
        );
        $this->getDatabase()->table('comment')->insertMultiple(
            ['user_id', 'post_id', 'public', 'content'],
            [
                [1, 1, true, 'Foo-bar-baz comment 1'],
                [1, 2, true, 'Foo-bar-baz comment 2'],
                [1, 1, true, 'Foo-bar-baz comment 3'],
            ],
        );
    }
}
