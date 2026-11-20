<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Feature\SQLite\Typecast;

use Cycle\ORM\Collection\ArrayCollectionFactory;
use Cycle\ORM\Config\RelationConfig;
use Cycle\ORM\Exception\FactoryTypecastException;
use Cycle\ORM\Factory;
use Cycle\ORM\Mapper\Mapper;
use Cycle\ORM\ORM;
use Cycle\ORM\Parser\CastableInterface;
use Cycle\ORM\Parser\CompositeTypecast;
use Cycle\ORM\Parser\Typecast;
use Cycle\ORM\Parser\TypecastInterface;
use Cycle\ORM\Schema;
use Cycle\ORM\SchemaInterface;
use Cycle\ORM\Service\TypecastProviderInterface;
use Cycle\ORM\Tests\Feature\Common\Typecast\Fixture\Book;
use Cycle\ORM\Tests\Feature\Common\Typecast\Fixture\InvalidTypecaster;
use Cycle\ORM\Tests\Feature\Common\Typecast\Fixture\Typecaster;
use Cycle\ORM\Tests\Util\SimpleFactory;
use Cycle\Testing\ORM\OrmTestCase;
use Spiral\Core\Container;
use Testo\Assert;
use Testo\Expect;
use Testo\Test;

final class SchemaTest extends OrmTestCase
{
    private const PRIMARY_ROLE = 'book';

    private ?Container $container;

    public function setUpOrm(array $bookSchema = [], array $factoryDefinitions = []): void
    {
        $container = $this->container ??= new Container();

        $this->orm = new ORM(
            new Factory(
                $this->dbal,
                RelationConfig::getDefault(),
                new SimpleFactory(
                    $factoryDefinitions,
                    static fn(string|object $alias, array $parameters = []): mixed => \is_string($alias)
                        ? $container->make($alias, $parameters) : $alias,
                ),
                new ArrayCollectionFactory(),
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
            ]),
        );
    }

    #[Test]
    public function testEmptyStringShouldThrowAnException(): void
    {
        Expect::exception(FactoryTypecastException::class)->withMessageMatchingRegex('/Bad typecast handler declaration for the `book` role./');

        $this->setUpOrm([
            SchemaInterface::TYPECAST_HANDLER => '',
        ]);

        $this->getTypecast(self::PRIMARY_ROLE);
    }

    #[Test]
    public function testHandlerWithWrongInterfaceShouldThrowAnException(): void
    {
        $this->setUpOrm([
            SchemaInterface::TYPECAST_HANDLER => InvalidTypecaster::class,
        ]);

        Expect::exception(FactoryTypecastException::class)->withMessageContaining('Bad typecast handler declaration for the `book` role. Cycle\ORM\Factory::makeTypecastHandler(): Return value must be of type Cycle\ORM\Parser\TypecastInterface, Cycle\ORM\Tests\Feature\Common\Typecast\Fixture\InvalidTypecaster returned');

        $this->orm->getService(TypecastProviderInterface::class)->getTypecast(self::PRIMARY_ROLE);
    }

    #[Test]
    public function testHandlerWithWrongInterfaceAmongArrayShouldThrowAnException(): void
    {
        $this->container = new Container();
        $this->container->bind('bar-foo', Typecaster::class);
        $this->setUpOrm([
            SchemaInterface::TYPECAST_HANDLER => [
                InvalidTypecaster::class,
                'bar-foo',
            ],
        ]);

        Expect::exception(FactoryTypecastException::class)->withMessageContaining('Bad typecast handler declaration for the `book` role. Cycle\ORM\Factory::makeTypecastHandler(): Return value must be of type Cycle\ORM\Parser\TypecastInterface, Cycle\ORM\Tests\Feature\Common\Typecast\Fixture\InvalidTypecaster returned');

        $this->orm->getService(TypecastProviderInterface::class)->getTypecast(self::PRIMARY_ROLE);
    }

    #[Test]
    public function testUseTypecastFromContainer(): void
    {
        $this->container = new Container();
        $this->container->bind('bar-foo', Typecaster::class);

        $this->setUpOrm([
            SchemaInterface::TYPECAST_HANDLER => 'bar-foo',
        ]);

        $typecast = $this->getTypecast(self::PRIMARY_ROLE);
        Assert::same($typecast::class, Typecaster::class);
    }

    #[Test]
    public function testUseTypecastAliasAsString(): void
    {
        $tc = new Typecaster(new Schema([]), self::PRIMARY_ROLE);
        $this->setUpOrm([
            SchemaInterface::TYPECAST_HANDLER => 'test-alias',
        ], ['test-alias' => &$tc]);

        $typecast = $this->getTypecast(self::PRIMARY_ROLE);

        Assert::same($typecast::class, Typecaster::class);
    }

    #[Test]
    public function testUseTypecastClassAsString(): void
    {
        $options = ['id' => 'int', 'published_at' => 'datetime(d-m-Y-H-i-s-u)'];
        $this->setUpOrm([
            SchemaInterface::TYPECAST => $options,
            SchemaInterface::TYPECAST_HANDLER => Typecaster::class,
        ]);

        /** @var Typecaster $typecast */
        $typecast = $this->getTypecast(self::PRIMARY_ROLE);

        Assert::same($typecast::class, Typecaster::class);
        Assert::same($typecast->role, self::PRIMARY_ROLE);
        Assert::same($typecast->rules, $options);
    }

    #[Test]
    public function testNullDefinitionAndEmptyRules(): void
    {
        $this->setUpOrm([
            SchemaInterface::TYPECAST_HANDLER => null,
        ]);

        $typecast = $this->getTypecast(self::PRIMARY_ROLE);

        Assert::null($typecast);
    }

    #[Test]
    public function testTypecastWithJti(): void
    {
        $this->setUpOrm();

        $foo = $this->getTypecast('foo');
        $bar = $this->getTypecast('bar');
        $baz = $this->getTypecast('baz');

        Assert::null($foo);
        Assert::same($bar::class, Typecast::class);
        Assert::same($baz::class, CompositeTypecast::class);
    }

    /**
     * @see CompositeTypecast::cast()
     */
    #[Test]
    public function testUseCompositeTypecast(): void
    {
        $containerTypecast = \Mockery::mock(CastableInterface::class)->shouldIgnoreMissing();
        $containerTypecast->shouldReceive('cast')->times(1)->with(['foo' => 'bar'], \Mockery::andAnyOtherArgs())->andReturn(['foo' => 'bar3']); // passes to $aliasTypecast

        $aliasTypecast = \Mockery::mock(CastableInterface::class)->shouldIgnoreMissing();
        $aliasTypecast->shouldReceive('cast')->times(1)->with(['foo' => 'bar3'], \Mockery::andAnyOtherArgs())->andReturn(['foo' => 'bar1']); // passes to $typecast

        $typecast = \Mockery::mock(CastableInterface::class)->shouldIgnoreMissing();
        $typecast->shouldReceive('cast')->times(1)->with(['foo' => 'bar1'], \Mockery::andAnyOtherArgs())->andReturn(['foo' => 'bar2']); // passes to Composite typecast

        $this->container = new Container();
        $this->container->bindSingleton('bar-foo', fn() => $containerTypecast);

        $this->setUpOrm([
            SchemaInterface::TYPECAST_HANDLER => [
                'bar-foo', // container
                'test-alias', // alias
                $typecast, // class string
            ],
        ], [
            'test-alias' => $aliasTypecast,
        ]);

        $typecast = $this->getTypecast(self::PRIMARY_ROLE);

        Assert::same($typecast::class, CompositeTypecast::class);
        Assert::same($typecast->cast(['foo' => 'bar']), ['foo' => 'bar2']);
    }

    private function getTypecast(string $role): ?TypecastInterface
    {
        return $this->orm->getService(TypecastProviderInterface::class)->getTypecast($role);
    }
}
