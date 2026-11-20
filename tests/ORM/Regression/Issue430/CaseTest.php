<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Regression\Issue430;

use Cycle\ORM\Relation\BulkLoader;
use Cycle\ORM\Select;
use Ramsey\Uuid\UuidInterface;
use Cycle\Testing\ORM\OrmTestCase;
use Cycle\Testing\Database\Assert\QueryAssert;

/**
 * BulkLoader with UUID object primary keys.
 */
class CaseTest extends OrmTestCase
{

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

    #[\Testo\Test]
    public function testBulkLoadHasMany(): void
    {
        $users = (new Select($this->orm, Entity\User::class))
            ->orderBy('login')
            ->fetchAll();

        \Testo\Assert::count($users, 3);
        // Bulk load posts for all users — single query
        QueryAssert::reads(1, fn() => (new BulkLoader($this->orm))
            ->collect(...$users)
            ->load('posts')
            ->run());
        // Verify posts are loaded without additional queries
        QueryAssert::reads(0, function () use (&$postCounts, $users): void {
            $postCounts = [];
            foreach ($users as $user) {
                $postCounts[$user->uuid->toString()] = \count($user->posts);
            }
        });

        \Testo\Assert::same($postCounts[self::USER_1], 1);
        \Testo\Assert::same($postCounts[self::USER_2], 2);
        \Testo\Assert::same($postCounts[self::USER_3], 1);
    }

    #[\Testo\Test]
    public function testBulkLoadBelongsTo(): void
    {
        $posts = (new Select($this->orm, Entity\Post::class))
            ->orderBy('title')
            ->fetchAll();

        \Testo\Assert::count($posts, 4);
        // Bulk load user for all posts — single query
        QueryAssert::reads(1, fn() => (new BulkLoader($this->orm))
            ->collect(...$posts)
            ->load('user')
            ->run());
        // Verify users are loaded without additional queries
        QueryAssert::reads(0, function () use ($posts, &$post): void {
            foreach ($posts as $post) {
                \Testo\Assert::instanceOf($post->user, Entity\User::class);
                \Testo\Assert::instanceOf($post->user->uuid, UuidInterface::class);
            }
        });

        // Verify correct user assignment
        $postsByUuid = [];
        foreach ($posts as $post) {
            $postsByUuid[$post->uuid->toString()] = $post;
        }
        \Testo\Assert::same($postsByUuid[self::POST_1]->user->uuid->toString(), self::USER_1);
        \Testo\Assert::same($postsByUuid[self::POST_2]->user->uuid->toString(), self::USER_2);
        \Testo\Assert::same($postsByUuid[self::POST_3]->user->uuid->toString(), self::USER_2);
        \Testo\Assert::same($postsByUuid[self::POST_4]->user->uuid->toString(), self::USER_3);
    }

    #[\Testo\Test]
    public function testBulkLoadMultipleRelations(): void
    {
        $posts = (new Select($this->orm, Entity\Post::class))
            ->fetchAll();

        \Testo\Assert::count($posts, 4);
        // Load both user and comments in one BulkLoader call
        QueryAssert::reads(3, fn() => (new BulkLoader($this->orm))
            ->collect(...$posts)
            ->load('user')
            ->load('comments')
            ->run());
        // 1 for users + 1 for comments + 1 for eager-loaded comment.user
        // Verify all relations loaded without queries
        QueryAssert::reads(0, function () use ($posts): void {
            foreach ($posts as $post) {
                \Testo\Assert::instanceOf($post->user, Entity\User::class);
                \Testo\Assert::array($post->comments);
            }
        });
    }

    #[\Testo\Test]
    public function testBulkLoadForSingleEntity(): void
    {
        $user = (new Select($this->orm, Entity\User::class))
            ->where('login', 'user-2')
            ->fetchOne();

        \Testo\Assert::instanceOf($user, Entity\User::class);
        QueryAssert::reads(1, fn() => (new BulkLoader($this->orm))
            ->collect($user)
            ->load('posts')
            ->run());
        QueryAssert::reads(0, fn() => \Testo\Assert::count($user->posts, 2));
    }

    #[\Testo\Test]
    public function testBulkLoadBelongsToOnComments(): void
    {
        // Comment has eager-loaded user and promise-loaded post
        $comments = (new Select($this->orm, Entity\Comment::class))
            ->fetchAll();

        \Testo\Assert::count($comments, 3);
        // Bulk load the lazy post relation
        QueryAssert::reads(1, fn() => (new BulkLoader($this->orm))
            ->collect(...$comments)
            ->load('post')
            ->run());
        QueryAssert::reads(0, function () use ($comments): void {
            foreach ($comments as $comment) {
                \Testo\Assert::instanceOf($comment->post, Entity\Post::class);
                \Testo\Assert::instanceOf($comment->post->uuid, UuidInterface::class);
            }
        });
    }

    #[\Testo\Lifecycle\BeforeTest]
    public function setUp(): void
    {
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
