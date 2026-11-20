<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Feature\Common\Relation\BelongsTo;

use Cycle\ORM\Mapper\Mapper;
use Cycle\ORM\Relation;
use Cycle\ORM\Schema;
use Cycle\ORM\SchemaInterface;
use Cycle\ORM\Tests\Fixtures\Comment;
use Cycle\ORM\Tests\Fixtures\UserWithUUIDPrimaryKey;
use Cycle\ORM\Tests\Fixtures\UuidPrimaryKey;
use Cycle\Testing\ORM\OrmTestCase;
use Ramsey\Uuid\Uuid;
use Testo\Assert;
use Testo\Lifecycle\BeforeTest;
use Testo\Test;

final class BelongsToRelationWithNestedUuidTest extends OrmTestCase
{
    #[Test]
    public function testNestedWithUuidPK(): void
    {
        $uuid = Uuid::uuid4();
        $user = new UserWithUUIDPrimaryKey(new UuidPrimaryKey($uuid->toString()), 'uuid@world.com', 5);
        $this->save($user);

        $comment = new Comment();
        $comment->userWithUuid = $user;
        $comment->message = 'foo';
        $this->save($comment);

        /** @var UserWithUUIDPrimaryKey $user */
        $user = $this->orm->getRepository(UserWithUUIDPrimaryKey::class)->findOne();
        /** @var Comment $comment */
        $comment = $this->orm->getRepository(Comment::class)->findOne();

        $childComment = new Comment();
        $childComment->parent = $comment;
        $childComment->userWithUuid = $user;
        $childComment->message = 'bar';
        $this->save($childComment);

        $first = $this->orm->getRepository(Comment::class)->findOne(['message' => 'foo']);
        $second = $this->orm->getRepository(Comment::class)->findOne(['message' => 'bar']);

        Assert::instanceOf($this->orm->getRepository(UserWithUUIDPrimaryKey::class)->findOne(), UserWithUUIDPrimaryKey::class);

        Assert::instanceOf($first, Comment::class);
        Assert::instanceOf($first->userWithUuid, UserWithUUIDPrimaryKey::class);
        Assert::null($first->parent);
        Assert::instanceOf($second, Comment::class);
        Assert::instanceOf($second->userWithUuid, UserWithUUIDPrimaryKey::class);
        Assert::instanceOf($second->parent, Comment::class);
    }

    #[BeforeTest]
    public function setUp(): void
    {
        $this->makeTable('user_with_uuid_pk', [
            'uuid' => 'string(36),primary',
            'email' => 'string',
            'balance' => 'float',
        ]);

        $this->makeTable('comment_with_uuid_user', [
            'id' => 'primary',
            'message' => 'string,nullable',
            'parent_id' => 'integer,nullable',
            'user_id' => 'string(36),nullable',
        ]);

        $this->orm = $this->withSchema(new Schema($this->getSchemaArray()));
    }

    private function getSchemaArray(): array
    {
        return [
            UserWithUUIDPrimaryKey::class => [
                SchemaInterface::ROLE => 'user_with_uuid_primary_key',
                SchemaInterface::MAPPER => Mapper::class,
                SchemaInterface::DATABASE => 'default',
                SchemaInterface::TABLE => 'user_with_uuid_pk',
                SchemaInterface::PRIMARY_KEY => 'uuid',
                SchemaInterface::COLUMNS => ['uuid', 'email', 'balance'],
                SchemaInterface::TYPECAST => [
                    'uuid' => [UuidPrimaryKey::class, 'typecast'],
                ],
            ],
            Comment::class => [
                Schema::ROLE => 'comment',
                Schema::MAPPER => Mapper::class,
                Schema::DATABASE => 'default',
                Schema::TABLE => 'comment_with_uuid_user',
                Schema::PRIMARY_KEY => 'id',
                Schema::COLUMNS => ['id', 'message', 'parent_id', 'user_id'],
                Schema::SCHEMA => [],
                Schema::TYPECAST => [
                    'id' => 'int',
                ],
                Schema::RELATIONS => [
                    'userWithUuid' => [
                        Relation::TYPE => Relation::BELONGS_TO,
                        Relation::TARGET => UserWithUUIDPrimaryKey::class,
                        Relation::SCHEMA => [
                            Relation::CASCADE => true,
                            Relation::NULLABLE => true,
                            Relation::INNER_KEY => 'user_id',
                            Relation::OUTER_KEY => 'uuid',
                        ],
                    ],
                    'parent' => [
                        Relation::TYPE => Relation::REFERS_TO,
                        Relation::TARGET => Comment::class,
                        Relation::SCHEMA => [
                            Relation::CASCADE => true,
                            Relation::NULLABLE => true,
                            Relation::INNER_KEY => 'parent_id',
                            Relation::OUTER_KEY => 'id',
                        ],
                    ],
                ],
            ],
        ];
    }
}
