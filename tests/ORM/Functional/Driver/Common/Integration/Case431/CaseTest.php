<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Functional\Driver\Common\Integration\Case431;

use Cycle\ORM\Relation\BulkLoader;
use Cycle\ORM\Select;
use Cycle\ORM\Tests\Functional\Driver\Common\BaseTest;
use Cycle\ORM\Tests\Functional\Driver\Common\Integration\IntegrationTestTrait;
use Cycle\ORM\Tests\Traits\TableTrait;

/**
 * BulkLoader with object primary keys and one-way typecast (CastableInterface only, no UncastableInterface).
 *
 * User uses OneWayUuidTypecast → StringableUuid (Stringable only).
 * Post uses OneWayValueInterfaceTypecast → ValueInterfaceUuid (ValueInterface + Stringable,
 *   where rawValue() returns the DB string and __toString() returns a different format).
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

    // =============================================
    // Stringable PK tests (User entity, StringableUuid)
    // =============================================

    public function testStringablePk_HasMany(): void
    {
        $users = (new Select($this->orm, Entity\User::class))
            ->orderBy('login')
            ->fetchAll();

        $this->assertCount(3, $users);

        $this->captureReadQueries();
        (new BulkLoader($this->orm))
            ->collect(...$users)
            ->load('posts')
            ->run();
        $this->assertNumReads(1);

        $this->captureReadQueries();
        $postCounts = [];
        foreach ($users as $user) {
            $this->assertInstanceOf(Typecast\StringableUuid::class, $user->id);
            $postCounts[$user->id->toString()] = \count($user->posts);
        }
        $this->assertNumReads(0);

        $this->assertSame(1, $postCounts[self::USER_1]);
        $this->assertSame(2, $postCounts[self::USER_2]);
        $this->assertSame(1, $postCounts[self::USER_3]);
    }

    public function testStringablePk_SingleEntity(): void
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

    // =============================================
    // ValueInterface PK tests (Post entity, ValueInterfaceUuid)
    // rawValue() returns DB-matching UUID, __toString() returns "urn:uuid:..."
    // If BulkLoader incorrectly uses __toString(), indexing won't match DB data.
    // =============================================

    public function testValueInterfacePk_HasMany(): void
    {
        $posts = (new Select($this->orm, Entity\Post::class))
            ->fetchAll();

        $this->assertCount(4, $posts);

        foreach ($posts as $post) {
            $this->assertInstanceOf(Typecast\ValueInterfaceUuid::class, $post->id);
        }

        $this->captureReadQueries();
        (new BulkLoader($this->orm))
            ->collect(...$posts)
            ->load('comments')
            ->run();
        $this->assertNumReads(2); // 1 comments + 1 eager comment.user

        $this->captureReadQueries();
        foreach ($posts as $post) {
            $this->assertIsArray($post->comments);
        }
        $this->assertNumReads(0);
    }

    public function testValueInterfacePk_BelongsTo(): void
    {
        $posts = (new Select($this->orm, Entity\Post::class))
            ->orderBy('title')
            ->fetchAll();

        $this->assertCount(4, $posts);

        $this->captureReadQueries();
        (new BulkLoader($this->orm))
            ->collect(...$posts)
            ->load('user')
            ->run();
        $this->assertNumReads(1);

        $this->captureReadQueries();
        foreach ($posts as $post) {
            $this->assertInstanceOf(Entity\User::class, $post->user);
        }
        $this->assertNumReads(0);
    }

    public function testValueInterfacePk_MultipleRelations(): void
    {
        $posts = (new Select($this->orm, Entity\Post::class))
            ->fetchAll();

        $this->assertCount(4, $posts);

        $this->captureReadQueries();
        (new BulkLoader($this->orm))
            ->collect(...$posts)
            ->load('user')
            ->load('comments')
            ->run();
        $this->assertNumReads(3); // 1 users + 1 comments + 1 eager comment.user

        $this->captureReadQueries();
        foreach ($posts as $post) {
            $this->assertInstanceOf(Entity\User::class, $post->user);
            $this->assertIsArray($post->comments);
        }
        $this->assertNumReads(0);
    }

    // =============================================
    // Cross-type: BulkLoad comments, load lazy post (ValueInterfaceUuid FK)
    // =============================================

    public function testStringablePk_BelongsToValueInterfaceEntity(): void
    {
        $comments = (new Select($this->orm, Entity\Comment::class))
            ->fetchAll();

        $this->assertCount(3, $comments);

        $this->captureReadQueries();
        (new BulkLoader($this->orm))
            ->collect(...$comments)
            ->load('post')
            ->run();
        $this->assertNumReads(1);

        $this->captureReadQueries();
        foreach ($comments as $comment) {
            $this->assertInstanceOf(Entity\Post::class, $comment->post);
            $this->assertInstanceOf(Typecast\ValueInterfaceUuid::class, $comment->post->id);
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
            'id' => 'string,primary',
            'login' => 'string',
            'password_hash' => 'string',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ]);

        $this->makeTable('post', [
            'id' => 'string,primary',
            'user_id' => 'string',
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
            'id' => 'string,primary',
            'public' => 'bool',
            'content' => 'string',
            'user_id' => 'string',
            'post_id' => 'string',
            'published_at' => 'datetime,nullable',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
            'deleted_at' => 'datetime,nullable',
        ]);
        $this->makeFK('comment', 'user_id', 'user', 'id', 'NO ACTION', 'NO ACTION');
        $this->makeFK('comment', 'post_id', 'post', 'id', 'NO ACTION', 'NO ACTION');
    }

    private function fillData(): void
    {
        $now = new \DateTimeImmutable();

        $this->getDatabase()->table('user')->insertMultiple(
            ['id', 'login', 'password_hash', 'created_at', 'updated_at'],
            [
                [self::USER_1, 'user-1', \md5('pass1'), $now, $now],
                [self::USER_2, 'user-2', \md5('pass2'), $now, $now],
                [self::USER_3, 'user-3', \md5('pass3'), $now, $now],
            ],
        );

        $this->getDatabase()->table('post')->insertMultiple(
            ['id', 'user_id', 'slug', 'title', 'public', 'content', 'created_at', 'updated_at'],
            [
                [self::POST_1, self::USER_1, 'slug-1', 'Post 1', true, 'Content 1', $now, $now],
                [self::POST_2, self::USER_2, 'slug-2', 'Post 2', true, 'Content 2', $now, $now],
                [self::POST_3, self::USER_2, 'slug-3', 'Post 3', true, 'Content 3', $now, $now],
                [self::POST_4, self::USER_3, 'slug-4', 'Post 4', true, 'Content 4', $now, $now],
            ],
        );

        $this->getDatabase()->table('comment')->insertMultiple(
            ['id', 'user_id', 'post_id', 'public', 'content', 'created_at', 'updated_at'],
            [
                [self::COMMENT_1, self::USER_1, self::POST_1, true, 'Comment 1', $now, $now],
                [self::COMMENT_2, self::USER_1, self::POST_2, true, 'Comment 2', $now, $now],
                [self::COMMENT_3, self::USER_2, self::POST_1, true, 'Comment 3', $now, $now],
            ],
        );
    }
}
