<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Functional\Driver\Common\Integration\Case428;

use Cycle\ORM\Select;
use Cycle\ORM\Tests\Functional\Driver\Common\BaseTest;
use Cycle\ORM\Tests\Functional\Driver\Common\Integration\IntegrationTestTrait;
use Cycle\ORM\Tests\Traits\TableTrait;

abstract class TestCase extends BaseTest
{
    use IntegrationTestTrait;
    use TableTrait;

    public function testSelect(): void
    {
        // Get entity
        $post = (new Select($this->orm, Entity\Post::class))->fetchOne();

        // Check result
        $this->assertInstanceOf(Entity\Post::class, $post);
        $this->assertInstanceOf(Entity\Comment::class, $post->best_comment);
        $this->assertSame(2, $post->best_comment->id);
    }

    public function testCreate(): void
    {
        // Get entity
        $post = new Entity\Post('New title', 'New content');
        $post->best_comment = new Entity\Comment('New comment content', $post);

        $this->enableProfiling();

        // Store changes and calc write queries
        $this->captureWriteQueries();
        $this->save($post);

        // Check write queries count
        $this->assertNumWrites(3);
    }

    public function setUp(): void
    {
        // Init DB
        parent::setUp();
        $this->makeTables();
        $this->fillData();

        $this->loadSchema(__DIR__ . '/schema.php');
    }

    private function makeTables(): void
    {
        $this->makeTable('user', [
            'id' => 'primary',
            'name' => 'string',
            'email' => 'string',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ]);

        $this->makeTable('category', [
            'id' => 'primary',
            'name' => 'string',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ]);

        $this->makeTable('post', [
            'id' => 'primary',
            'title' => 'string',
            'content' => 'string',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
            'best_comment_id' => 'int,nullable',
            'user_id' => 'int,nullable',
            'category_id' => 'int,nullable',
        ]);

        $this->makeTable('comment', [
            'id' => 'primary',
            'content' => 'string',
            'post_id' => 'int',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ]);

        $this->makeFK('post', 'user_id', 'user', 'id', 'SET NULL', 'SET NULL');
        $this->makeFK('post', 'category_id', 'category', 'id', 'SET NULL', 'SET NULL');
        $this->makeFK('comment', 'post_id', 'post', 'id', 'NO ACTION', 'NO ACTION');
        $this->makeFK('post', 'best_comment_id', 'comment', 'id', 'SET NULL', 'SET NULL');
    }

    private function fillData(): void
    {
        $this->getDatabase()->table('user')->insertMultiple(
            ['id', 'name', 'email'],
            [
                [1, 'John Doe', 'john@example.com'],
                [2, 'Jane Smith', 'jane@example.com'],
            ],
        );

        $this->getDatabase()->table('category')->insertMultiple(
            ['id', 'name'],
            [
                [1, 'Technology'],
                [2, 'Science'],
            ],
        );

        $this->getDatabase()->table('post')->insertMultiple(
            ['id', 'title', 'content', 'best_comment_id', 'user_id', 'category_id'],
            [
                [1, 'Title 1', 'Foo-bar-baz content 1', 2, 1, 1],
            ],
        );

        $this->getDatabase()->table('comment')->insertMultiple(
            ['post_id', 'content'],
            [
                [1, 'Foo-bar-baz comment 1'],
                [1, 'Foo-bar-baz comment 2'],
                [1, 'Foo-bar-baz comment 3'],
            ],
        );
    }
}
