<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Functional\Driver\Common;

use Cycle\ORM\Command\CommandInterface;
use Cycle\ORM\Mapper\Mapper;
use Cycle\ORM\ORMInterface;
use Cycle\ORM\Schema;
use Cycle\ORM\Schema\GeneratedField;
use Cycle\ORM\SchemaInterface;
use Cycle\ORM\Select;
use Cycle\ORM\Tests\Fixtures\CyclicRef2\Document;
use Cycle\ORM\Tests\Fixtures\User;
use Cycle\ORM\Tests\Traits\TableTrait;
use Cycle\ORM\Tests\Util\DontGenerateAttribute;
use Cycle\ORM\Transaction;
use DateTimeImmutable;
use Ramsey\Uuid\Uuid;

#[DontGenerateAttribute]
abstract class GeneratedColumnTest extends BaseTest
{
    use TableTrait;

    public function setUp(): void
    {
        parent::setUp();

        $this->createTables();

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
                    'balance' => GeneratedField::ON_INSERT, // sequence
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
                SchemaInterface::TYPECAST => [
                    'created_at' => 'datetime',
                    'updated_at' => 'datetime',
                ],
                SchemaInterface::GENERATED_FIELDS => [
                    'id' => GeneratedField::ON_INSERT,
                    'body' => GeneratedField::ON_INSERT,
                    'created_at' => GeneratedField::ON_INSERT,
                    'updated_at' => GeneratedField::BEFORE_INSERT | GeneratedField::BEFORE_UPDATE,
                ],
            ],
        ]));
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

        $d2 = new Document();
        $d2->body = 213;
        $d2->created_at = $d2->updated_at = new DateTimeImmutable('2020-01-01');

        $d3 = new Document();
        $d3->created_at = $d3->updated_at = new DateTimeImmutable('2020-01-01');


        $this->save($d1, $d2, $d3);

        $this->assertEquals(1, $d1->id);
        $this->assertEquals(1, $d1->body);
        $this->assertNotSame('2020-01-01', $d1->created_at->format('Y-m-d'));
        $this->assertEquals(2, $d2->id);
        $this->assertEquals(213, $d2->body);
        $this->assertSame('2020-01-01', $d2->created_at->format('Y-m-d'));
        $this->assertEquals(3, $d3->id);
        $this->assertEquals(2, $d3->body);
        $this->assertSame('2020-01-01', $d3->created_at->format('Y-m-d'));
    }

    protected function getCommandGenerator(): ?Transaction\CommandGeneratorInterface
    {
        return new class () extends Transaction\CommandGenerator {
            protected function storeEntity(ORMInterface $orm, Transaction\Tuple $tuple, bool $isNew): ?CommandInterface
            {
                /** @var CommandInterface|null $command */
                $command = parent::storeEntity($orm, $tuple, $isNew);

                if ($command !== null && $tuple->entity instanceof Document && empty($tuple->entity->updated_at)) {
                    $now = new DateTimeImmutable();
                    $tuple->state->register('updated_at', $now);
                    $tuple->entity->updated_at = $now;
                }

                return $command;
            }
        };
    }

    abstract public function createTables(): void;
}
