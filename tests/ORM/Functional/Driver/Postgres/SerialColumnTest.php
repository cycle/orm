<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Functional\Driver\Postgres;

use Cycle\ORM\Mapper\Mapper;
use Cycle\ORM\Schema;
use Cycle\ORM\SchemaInterface;
use Cycle\ORM\Select;
use Cycle\ORM\Tests\Fixtures\User;
use Cycle\ORM\Tests\Functional\Driver\Common\BaseTest;
use Cycle\ORM\Tests\Traits\TableTrait;
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
        ]));
    }

    public function testPersist(): void
    {
        $this->logger->display();
        $u = new User();
        $u->id = Uuid::uuid4()->toString();

        $this->save($u);

        $this->assertNotNull($u->balance);

        $this->orm->getHeap()->clean();

        $s = (new Select($this->orm, User::class))->wherePK($u->id)->fetchOne();

        $this->assertSame($u->balance, $s->balance);
    }
}
