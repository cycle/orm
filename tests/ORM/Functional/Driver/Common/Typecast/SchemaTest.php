<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Functional\Driver\Common\Typecast;

use Cycle\ORM\Collection\ArrayCollectionFactory;
use Cycle\ORM\Config\RelationConfig;
use Cycle\ORM\Factory;
use Cycle\ORM\Mapper\Mapper;
use Cycle\ORM\ORM;
use Cycle\ORM\Parser\CompositeTypecast;
use Cycle\ORM\Parser\Typecast;
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
                'foo' => [
                    SchemaInterface::MAPPER => Mapper::class,
                    SchemaInterface::DATABASE => 'default',
                    SchemaInterface::TABLE => 'foo',
                    SchemaInterface::PRIMARY_KEY => 'id',
                    SchemaInterface::COLUMNS => ['id', 'foo'],
                ],
                'bar' => [
                    SchemaInterface::MAPPER => Mapper::class,
                    SchemaInterface::DATABASE => 'default',
                    SchemaInterface::TABLE => 'bar',
                    SchemaInterface::PARENT => 'foo',
                    SchemaInterface::PRIMARY_KEY => 'id',
                    SchemaInterface::COLUMNS => ['id', 'bar'],
                    SchemaInterface::TYPECAST => ['id' => 'int'],
                ],
                'baz' => [
                    SchemaInterface::MAPPER => Mapper::class,
                    SchemaInterface::DATABASE => 'default',
                    SchemaInterface::TABLE => 'baz',
                    SchemaInterface::PARENT => 'bar',
                    SchemaInterface::PRIMARY_KEY => 'id',
                    SchemaInterface::COLUMNS => ['id', 'baz'],
                    SchemaInterface::TYPECAST => ['baz' => 'int'],
                ],
            ])
        );
    }

    public function testEmptyString(): void
    {
        $this->setUpOrm([
            SchemaInterface::TYPECAST_HANDLER => '',
        ]);

        $this->expectException(NotFoundExceptionInterface::class);

        $this->orm->getEntityRegistry()->getTypecast(self::PRIMARY_ROLE);
    }

    public function testUseTypecastAlis(): void
    {
        $this->setUpOrm([
            SchemaInterface::TYPECAST_HANDLER => 'test-alias',
        ], ['test-alias' => &$tc]);
        $tc = new Typecaster($this->orm, self::PRIMARY_ROLE);

        $typecast = $this->orm->getEntityRegistry()->getTypecast(self::PRIMARY_ROLE);

        $this->assertSame(Typecaster::class, $typecast::class);
    }

    public function testUseTypecastClass(): void
    {
        $options = ['id' => 'int', 'published_at' => 'datetime(d-m-Y-H-i-s-u)'];
        $this->setUpOrm([
            SchemaInterface::TYPECAST => $options,
            SchemaInterface::TYPECAST_HANDLER => Typecaster::class,
        ]);

        /** @var Typecaster $typecast */
        $typecast = $this->orm->getEntityRegistry()->getTypecast(self::PRIMARY_ROLE);

        $this->assertSame(Typecaster::class, $typecast::class);
        $this->assertSame(self::PRIMARY_ROLE, $typecast->role);
        $this->assertSame($options, $typecast->rules);
    }

    public function testNullDefinitionAndEmptyRules(): void
    {
        $this->setUpOrm([
            SchemaInterface::TYPECAST_HANDLER => null,
        ]);

        $typecast = $this->orm->getEntityRegistry()->getTypecast(self::PRIMARY_ROLE);

        $this->assertNull($typecast);
    }

    public function testTypecastWithJti(): void
    {
        $this->setUpOrm();

        $foo = $this->orm->getEntityRegistry()->getTypecast('foo');
        $bar = $this->orm->getEntityRegistry()->getTypecast('bar');
        $baz = $this->orm->getEntityRegistry()->getTypecast('baz');

        $this->assertNull($foo);
        $this->assertSame(Typecast::class, $bar::class);
        $this->assertSame(CompositeTypecast::class, $baz::class);
    }
}
