<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Functional\Driver\Common\Relation\BelongsTo;

use Cycle\ORM\Mapper\Mapper;
use Cycle\ORM\Relation;
use Cycle\ORM\Schema;
use Cycle\ORM\SchemaInterface;
use Cycle\ORM\Tests\Fixtures\Comment;
use Cycle\ORM\Tests\Fixtures\UserWithUUIDPrimaryKey;
use Cycle\ORM\Tests\Fixtures\UuidPrimaryKey;
use Cycle\ORM\Tests\Functional\Driver\Common\BaseTest;
use Cycle\ORM\Tests\Traits\TableTrait;
use Ramsey\Uuid\Uuid;

abstract class BelongsToRelationWithNestedUuidTest extends BaseTest
{
    use TableTrait;

    public function setUp(): void
    {
        parent::setUp();

        $this->makeTable('user', [
            'uuid' => 'string(36),primary',
            'email' => 'string',
            'balance' => 'float',
        ]);

        $this->makeTable('comment', [
            'id' => 'primary',
            'parent_id' => 'integer,nullable',
            'user_id' => 'string(36),nullable',
        ]);

        $this->orm = $this->withSchema(new Schema($this->getSchemaArray()));
    }

    public function testNestedWithUuidPK(): void
    {
        $uuid = Uuid::uuid4();
        $user = new UserWithUUIDPrimaryKey(new UuidPrimaryKey($uuid->toString()), 'uuid@world.com', 5);
        $this->save($user);

        $comment = new Comment();
        $comment->userWithUuid = $user;
        $this->save($comment);

        /** @var UserWithUUIDPrimaryKey $user */
        $user = $this->orm->getRepository(UserWithUUIDPrimaryKey::class)->findOne();
        /** @var Comment $comment */
        $comment = $this->orm->getRepository(Comment::class)->findOne();

        $childComment = new Comment();
        $childComment->parent = $comment;
        $childComment->userWithUuid = $user;
        $this->save($childComment);

        $this->assertInstanceOf(
            UserWithUUIDPrimaryKey::class,
            $this->orm->getRepository(UserWithUUIDPrimaryKey::class)->findOne()
        );
        $comments = $this->orm->getRepository(Comment::class)->findAll();

        $this->assertInstanceOf(UserWithUUIDPrimaryKey::class, $comments[0]->userWithUuid);
        $this->assertInstanceOf(UserWithUUIDPrimaryKey::class, $comments[1]->userWithUuid);
        $this->assertInstanceOf(Comment::class, $comments[1]->parent);
    }

    private function getSchemaArray(): array
    {
        return [
            UserWithUUIDPrimaryKey::class => [
                SchemaInterface::ROLE => 'user_with_uuid_primary_key',
                SchemaInterface::MAPPER => Mapper::class,
                SchemaInterface::DATABASE => 'default',
                SchemaInterface::TABLE => 'user',
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
                Schema::TABLE => 'comment',
                Schema::PRIMARY_KEY => 'id',
                Schema::COLUMNS => ['id', 'parent_id', 'user_id'],
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
