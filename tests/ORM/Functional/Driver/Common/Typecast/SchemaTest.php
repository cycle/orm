<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Functional\Driver\Common\Typecast;

use Cycle\ORM\Collection\ArrayCollectionFactory;
use Cycle\ORM\Config\RelationConfig;
use Cycle\ORM\Factory;
use Cycle\ORM\Mapper\Mapper;
use Cycle\ORM\ORM;
use Cycle\ORM\Schema;
use Cycle\ORM\SchemaInterface;
use Cycle\ORM\Tests\Functional\Driver\Common\Typecast\Fixture\Book;
use Cycle\ORM\Tests\Functional\Driver\Common\BaseTest;
use Cycle\ORM\Tests\Functional\Driver\Common\Typecast\Fixture\Typecaster;
use Cycle\ORM\Tests\Util\SimpleFactory;
use Psr\Container\NotFoundExceptionInterface;
use Spiral\Core\Container;

final class SchemaTest extends BaseTest
{
    public const DRIVER = 'sqlite';

    private const PRIMARY_ROLE = 'book';

    public function setUpOrm(array $bookSchema = [], array $factoryDefinitions = []): void
    {
        $container = new Container();
        $this->orm = new ORM(
            new Factory(
                $this->dbal,
                RelationConfig::getDefault(),
                new SimpleFactory(
                    $factoryDefinitions,
                    static fn (string $alias, array $parameters = []): mixed => $container->make($alias, $parameters)
                ),
                new ArrayCollectionFactory()
            ),
            new Schema([
                Book::class => $bookSchema + [
                    SchemaInterface::ROLE => self::PRIMARY_ROLE,
                    SchemaInterface::MAPPER => Mapper::class,
                    SchemaInterface::DATABASE => 'default',
                    SchemaInterface::TABLE => 'book',
                    SchemaInterface::PRIMARY_KEY => 'id',
                    SchemaInterface::COLUMNS => ['id', 'states', 'nested_states', 'published_at'],
                    SchemaInterface::RELATIONS => [],
                ],
            ])
        );
    }

    public function testEmptyString(): void
    {
        $this->setUpOrm([
            SchemaInterface::TYPECAST => '',
        ]);

        $this->expectException(NotFoundExceptionInterface::class);

        $this->orm->getEntityRegistry()->getTypecast(self::PRIMARY_ROLE);
    }

    public function testUseTypecastAlis(): void
    {
        $this->setUpOrm([
            SchemaInterface::TYPECAST => 'test-alias',
        ], ['test-alias' => &$tc]);
        $tc = new Typecaster($this->orm, self::PRIMARY_ROLE);

        $typecast = $this->orm->getEntityRegistry()->getTypecast(self::PRIMARY_ROLE);

        $this->assertSame(Typecaster::class, $typecast::class);
    }

    public function testNullValue(): void
    {
        $this->setUpOrm([
            SchemaInterface::TYPECAST => null,
        ]);

        $typecast = $this->orm->getEntityRegistry()->getTypecast(self::PRIMARY_ROLE);

        $this->assertNull($typecast);
    }
}
