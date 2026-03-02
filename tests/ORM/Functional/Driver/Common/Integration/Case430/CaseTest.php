<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Functional\Driver\Common\Integration\Case430;

use Cycle\ORM\Relation\BulkLoader;
use Cycle\ORM\Select;
use Cycle\ORM\Tests\Functional\Driver\Common\BaseTest;
use Cycle\ORM\Tests\Functional\Driver\Common\Integration\IntegrationTestTrait;
use Cycle\ORM\Tests\Traits\TableTrait;
use Ramsey\Uuid\UuidInterface;

/**
 * BulkLoader with UUID object primary keys.
 */
abstract class CaseTest extends BaseTest
{
    use IntegrationTestTrait;
    use TableTrait;

    private const USER_1 = '550e8400-e29b-41d4-a716-446655440001';
    private const USER_2 = '550e8400-e29b-41d4-a716-446655440002';
    private const USER_3 = '550e8400-e29b-41d4-a716-446655440003';
    private const POST_1 = '660e8400-e29b-41d4-a716-446655440001';
    private const POST_2 = '660e8400-e29b-41d4-a716-446655440002';
    private const POST_3 = '660e8400-e29b-41d4-a716-446655440003';
    private const POST_4 = '660e8400-e29b-41d4-a716-446655440004';
    private const COMMENT_1 = '770e8400-e29b-41d4-a716-446655440001';
    private const COMMENT_2 = '770e8400-e29b-41d4-a716-446655440002';
    private const COMMENT_3 = '770e8400-e29b-41d4-a716-446655440003';

    public function testBulkLoadHasMany(): void
    {
        $users = (new Select($this->orm, Entity\User::class))
            ->orderBy('login')
            ->fetchAll();

        $this->assertCount(3, $users);

        // Bulk load posts for all users — single query
        $this->captureReadQueries();
        (new BulkLoader($this->orm))
            ->collect(...$users)
            ->load('posts')
            ->run();
        $this->assertNumReads(1);

        // Verify posts are loaded without additional queries
        $this->captureReadQueries();
        $postCounts = [];
        foreach ($users as $user) {
            $postCounts[$user->uuid->toString()] = \count($user->posts);
        }
        $this->assertNumReads(0);

        $this->assertSame(1, $postCounts[self::USER_1]);
        $this->assertSame(2, $postCounts[self::USER_2]);
        $this->assertSame(1, $postCounts[self::USER_3]);
    }

    public function testBulkLoadBelongsTo(): void
    {
        $posts = (new Select($this->orm, Entity\Post::class))
            ->orderBy('title')
            ->fetchAll();

        $this->assertCount(4, $posts);

        // Bulk load user for all posts — single query
        $this->captureReadQueries();
        (new BulkLoader($this->orm))
            ->collect(...$posts)
            ->load('user')
            ->run();
        $this->assertNumReads(1);

        // Verify users are loaded without additional queries
        $this->captureReadQueries();
        foreach ($posts as $post) {
            $this->assertInstanceOf(Entity\User::class, $post->user);
            $this->assertInstanceOf(UuidInterface::class, $post->user->uuid);
        }
        $this->assertNumReads(0);

        // Verify correct user assignment
        $postsByUuid = [];
        foreach ($posts as $post) {
            $postsByUuid[$post->uuid->toString()] = $post;
        }
        $this->assertSame(self::USER_1, $postsByUuid[self::POST_1]->user->uuid->toString());
        $this->assertSame(self::USER_2, $postsByUuid[self::POST_2]->user->uuid->toString());
        $this->assertSame(self::USER_2, $postsByUuid[self::POST_3]->user->uuid->toString());
        $this->assertSame(self::USER_3, $postsByUuid[self::POST_4]->user->uuid->toString());
    }

    public function testBulkLoadMultipleRelations(): void
    {
        $posts = (new Select($this->orm, Entity\Post::class))
            ->fetchAll();

        $this->assertCount(4, $posts);

        // Load both user and comments in one BulkLoader call
        $this->captureReadQueries();
        (new BulkLoader($this->orm))
            ->collect(...$posts)
            ->load('user')
            ->load('comments')
            ->run();
        $this->assertNumReads(3); // 1 for users + 1 for comments + 1 for eager-loaded comment.user

        // Verify all relations loaded without queries
        $this->captureReadQueries();
        foreach ($posts as $post) {
            $this->assertInstanceOf(Entity\User::class, $post->user);
            $this->assertIsArray($post->comments);
        }
        $this->assertNumReads(0);
    }

    public function testBulkLoadForSingleEntity(): void
    {
        $user = (new Select($this->orm, Entity\User::class))
            ->where('login', 'user-2')
            ->fetchOne();

        $this->assertInstanceOf(Entity\User::class, $user);

        $this->captureReadQueries();
        (new BulkLoader($this->orm))
            ->collect($user)
            ->load('posts')
            ->run();
        $this->assertNumReads(1);

        $this->captureReadQueries();
        $this->assertCount(2, $user->posts);
        $this->assertNumReads(0);
    }

    public function testBulkLoadBelongsToOnComments(): void
    {
        // Comment has eager-loaded user and promise-loaded post
        $comments = (new Select($this->orm, Entity\Comment::class))
            ->fetchAll();

        $this->assertCount(3, $comments);

        // Bulk load the lazy post relation
        $this->captureReadQueries();
        (new BulkLoader($this->orm))
            ->collect(...$comments)
            ->load('post')
            ->run();
        $this->assertNumReads(1);

        $this->captureReadQueries();
        foreach ($comments as $comment) {
            $this->assertInstanceOf(Entity\Post::class, $comment->post);
            $this->assertInstanceOf(UuidInterface::class, $comment->post->uuid);
        }
        $this->assertNumReads(0);
    }

    public function setUp(): void
    {
        parent::setUp();
        $this->makeTables();
        $this->fillData();

        $this->loadSchema(__DIR__ . '/schema.php');
    }

    private function makeTables(): void
    {
        $this->makeTable(Entity\User::ROLE, [
            'uuid' => 'string,primary',
            'login' => 'string',
            'password_hash' => 'string',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ]);

        $this->makeTable('post', [
            'uuid' => 'string,primary',
            'user_uuid' => 'string',
            'slug' => 'string',
            'title' => 'string',
            'public' => 'bool',
            'content' => 'string',
            'published_at' => 'datetime,nullable',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
            'deleted_at' => 'datetime,nullable',
        ]);
        $this->makeFK('post', 'user_uuid', 'user', 'uuid', 'NO ACTION', 'NO ACTION');

        $this->makeTable('comment', [
            'uuid' => 'string,primary',
            'public' => 'bool',
            'content' => 'string',
            'user_uuid' => 'string',
            'post_uuid' => 'string',
            'published_at' => 'datetime,nullable',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
            'deleted_at' => 'datetime,nullable',
        ]);
        $this->makeFK('comment', 'user_uuid', 'user', 'uuid', 'NO ACTION', 'NO ACTION');
        $this->makeFK('comment', 'post_uuid', 'post', 'uuid', 'NO ACTION', 'NO ACTION');
    }

    private function fillData(): void
    {
        $now = new \DateTimeImmutable();

        $this->getDatabase()->table('user')->insertMultiple(
            ['uuid', 'login', 'password_hash', 'created_at', 'updated_at'],
            [
                [self::USER_1, 'user-1', \md5('pass1'), $now, $now],
                [self::USER_2, 'user-2', \md5('pass2'), $now, $now],
                [self::USER_3, 'user-3', \md5('pass3'), $now, $now],
            ],
        );

        $this->getDatabase()->table('post')->insertMultiple(
            ['uuid', 'user_uuid', 'slug', 'title', 'public', 'content', 'created_at', 'updated_at'],
            [
                [self::POST_1, self::USER_1, 'slug-1', 'Post 1', true, 'Content 1', $now, $now],
                [self::POST_2, self::USER_2, 'slug-2', 'Post 2', true, 'Content 2', $now, $now],
                [self::POST_3, self::USER_2, 'slug-3', 'Post 3', true, 'Content 3', $now, $now],
                [self::POST_4, self::USER_3, 'slug-4', 'Post 4', true, 'Content 4', $now, $now],
            ],
        );

        $this->getDatabase()->table('comment')->insertMultiple(
            ['uuid', 'user_uuid', 'post_uuid', 'public', 'content', 'created_at', 'updated_at'],
            [
                [self::COMMENT_1, self::USER_1, self::POST_1, true, 'Comment 1', $now, $now],
                [self::COMMENT_2, self::USER_1, self::POST_2, true, 'Comment 2', $now, $now],
                [self::COMMENT_3, self::USER_2, self::POST_1, true, 'Comment 3', $now, $now],
            ],
        );
    }
}
