<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Functional\Driver\Common\Integration\Case316;

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
        $this->assertTrue(true);

        $post = new Entity\Post('foo', 'bar');
        $post->user_id = 1;
        $this->save($post);

        // Get entity
        $comment = (new Select($this->orm, Entity\Comment::class))
            ->load('post')
            ->wherePK(2)
            ->fetchOne();

        // Check result
        $this->assertInstanceOf(Entity\Comment::class, $comment);
        $this->assertInstanceOf(Entity\Post::class, $comment->post);
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

    protected function makeTables(): void
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
            'content' => 'binary',
            'published_at' => 'datetime,nullable',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
            'deleted_at' => 'datetime,nullable',
        ]);
        $this->makeFK('post', 'user_id', 'user', 'id', 'NO ACTION', 'NO ACTION');

        $this->makeTable('comment', [
            'id' => 'primary',
            'public' => 'bool',
            'content' => 'binary',
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
        $postId1 = BinaryId::create();
        $postId2 = BinaryId::create();
        $postId3 = BinaryId::create();
        $this->getDatabase()
            ->insert('post')
            ->columns(['id', 'user_id', 'slug', 'title', 'public', 'content'])
            ->values([$postId1, 1, 'slug-string-1', 'Title 1', true, 'Foo-bar-baz content 1'])
            ->run();
        // $this->getDatabase()->table('post')->insertMultiple(
        //     ['id', 'user_id', 'slug', 'title', 'public', 'content'],
        //     [
        //         [$postId1, 1, 'slug-string-1', 'Title 1', true, 'Foo-bar-baz content 1'],
        //         // [$postId2, 2, 'slug-string-2', 'Title 2', true, 'Foo-bar-baz content 2'],
        //         // [$postId2, 2, 'slug-string-3', 'Title 3', true, 'Foo-bar-baz content 3'],
        //         // [$postId3, 3, 'slug-string-4', 'Title 4', true, 'Foo-bar-baz content 4'],
        //         // [$postId3, 3, 'slug-string-5', 'Title 5', true, 'Foo-bar-baz content 5'],
        //         // [$postId3, 3, 'slug-string-6', 'Title 6', true, 'Foo-bar-baz content 6'],
        //     ],
        // );
        // $this->getDatabase()->table('comment')->insertMultiple(
        //     ['user_id', 'post_id', 'public', 'content'],
        //     [
        //         [$postId1, 1, true, 'Foo-bar-baz comment 1'],
        //         // [$postId1, 2, true, 'Foo-bar-baz comment 2'],
        //         // [$postId1, 1, true, 'Foo-bar-baz comment 3'],
        //     ],
        // );
    }
}
