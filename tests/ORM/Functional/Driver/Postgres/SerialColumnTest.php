<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Functional\Driver\Postgres;

use Cycle\ORM\Mapper\Mapper;
use Cycle\ORM\Schema;
use Cycle\ORM\SchemaInterface;
use Cycle\ORM\Select;
use Cycle\ORM\Tests\Fixtures\CyclicRef2\Document;
use Cycle\ORM\Tests\Fixtures\User;
use Cycle\ORM\Tests\Functional\Driver\Common\BaseTest;
use Cycle\ORM\Tests\Traits\TableTrait;
use DateTimeImmutable;
use Ramsey\Uuid\Uuid;

/**
 * @group driver
 * @group driver-postgres
 */
final class SerialColumnTest extends BaseTest
{
    public const DRIVER = 'postgres';
    use TableTrait;

    public function setUp(): void
    {
        parent::setUp();

        $schema = $this->getDatabase()->table('user')->getSchema();
        $schema->column('id')->type('uuid');
        $schema->column('balance')->type('serial')->nullable(false);
        $schema->save();

        $this->getDatabase()->table('user')->insertMultiple(
            ['id'],
            [
                [Uuid::uuid4()->toString()],
                [Uuid::uuid4()->toString()],
                [Uuid::uuid4()->toString()],
            ]
        );

        $schema = $this->getDatabase()->table('document')->getSchema();
        $schema->column('id')->primary();
        $schema->column('body')->type('serial')->nullable(false);
        $schema->column('created_at')->type('datetime')->nullable(false);
        $schema->column('updated_at')->type('datetime')->nullable(false);
        $schema->save();

        $this->orm = $this->withSchema(new Schema([
            User::class => [
                SchemaInterface::ROLE => 'user',
                SchemaInterface::MAPPER => Mapper::class,
                SchemaInterface::DATABASE => 'default',
                SchemaInterface::TABLE => 'user',
                SchemaInterface::PRIMARY_KEY => 'id',
                SchemaInterface::COLUMNS => ['id', 'balance'],
                SchemaInterface::SCHEMA => [],
                SchemaInterface::RELATIONS => [],
                SchemaInterface::GENERATED_FIELDS => [
                    'balance' => SchemaInterface::GENERATED_DB, // sequence
                ],
            ],
            Document::class => [
                SchemaInterface::ROLE => 'profile',
                SchemaInterface::MAPPER => Mapper::class,
                SchemaInterface::DATABASE => 'default',
                SchemaInterface::TABLE => 'document',
                SchemaInterface::PRIMARY_KEY => 'id',
                SchemaInterface::COLUMNS => ['id', 'body', 'created_at', 'updated_at'],
                SchemaInterface::SCHEMA => [],
                SchemaInterface::RELATIONS => [],
                SchemaInterface::GENERATED_FIELDS => [
                    'id' => SchemaInterface::GENERATED_DB,
                    'body' => SchemaInterface::GENERATED_DB,
                    'created_at' => SchemaInterface::GENERATED_PHP_INSERT,
                    'updated_at' => SchemaInterface::GENERATED_PHP_INSERT | SchemaInterface::GENERATED_PHP_UPDATE,
                ],
            ],
        ]));
        $this->logger->display();
    }

    public function testPersist(): void
    {
        $u = new User();
        $u->id = Uuid::uuid4()->toString();

        $this->save($u);

        $this->assertNotNull($u->balance);

        $this->orm->getHeap()->clean();

        $s = (new Select($this->orm, User::class))->wherePK($u->id)->fetchOne();

        $this->assertSame($u->balance, $s->balance);
    }

    public function testPersistMultipleSerial(): void
    {
        $d1 = new Document();
        $d1->created_at = $d1->updated_at = new DateTimeImmutable();

        $d2 = new Document();
        $d2->body = 213;
        $d2->created_at = $d2->updated_at = new DateTimeImmutable();

        $d3 = new Document();
        $d3->created_at = $d3->updated_at = new DateTimeImmutable();


        $this->save($d1, $d2, $d3);

        $this->assertSame(1, $d1->id);
        $this->assertSame(1, $d1->body);
        $this->assertSame(2, $d2->id);
        $this->assertSame(213, $d2->body);
        $this->assertSame(3, $d3->id);
        $this->assertSame(2, $d3->body);
    }
}
