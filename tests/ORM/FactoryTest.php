<?php

namespace Cycle\ORM\Tests;

use Cycle\ORM\Exception\TypecastException;
use Cycle\ORM\Factory;
use Cycle\ORM\Mapper\Mapper;
use Cycle\ORM\Schema;
use Cycle\ORM\Select;
use Cycle\ORM\Select\Repository;
use Cycle\ORM\Select\Source;
use Cycle\ORM\Tests\Fixtures\DifferentSource;
use Cycle\ORM\Tests\Fixtures\TimestampedMapper;
use Cycle\ORM\Tests\Fixtures\User;
use Cycle\ORM\Tests\Fixtures\UserRepository;

class FactoryTest extends BaseTest
{
    // currently active driver
    public const DRIVER = 'postgres';
    /**
     * @var Factory
     */
    private $factory;

    public function setUp(): void
    {
        parent::setUp();

        $this->orm = $this->withSchema(new Schema([
            User::class => [
                Schema::ROLE => 'user',
                Schema::DATABASE => 'default',
                Schema::TABLE => 'user',
                Schema::PRIMARY_KEY => 'id',
                Schema::COLUMNS => ['id', 'email', 'balance'],
                Schema::SCHEMA => [],
                Schema::RELATIONS => []
            ]
        ]));

        $this->factory = new Factory($this->dbal);
    }

    public function testShouldMakeDefaultMapper()
    {
        $mapper = $this->factory->mapper($this->orm, $this->orm->getSchema(), 'user');

        self::assertInstanceOf(Mapper::class, $mapper);
    }

    public function testShouldChangeDefaultMapperClass()
    {
        $this->factory = $this->factory->withDefaultSchemaClasses([
            Schema::MAPPER => TimestampedMapper::class,
        ]);

        $mapper = $this->factory->mapper($this->orm, $this->orm->getSchema(), 'user');

        self::assertInstanceOf(TimestampedMapper::class, $mapper);
    }

    public function testShouldThrowExceptionIfDefaultMapperClassNotImplementMapperInterface()
    {
        $this->factory = $this->factory->withDefaultSchemaClasses([
            Schema::MAPPER => User::class,
        ]);

        self::expectException(TypecastException::class);

        $this->factory->mapper($this->orm, $this->orm->getSchema(), 'user');
    }

    public function testShouldMakeDefaultRepository()
    {
        $result = $this->factory->repository($this->orm, $this->orm->getSchema(), 'user', new Select($this->orm, 'user'));

        self::assertInstanceOf(Repository::class, $result);
    }

    public function testShouldChangeDefaultRepositoryClass()
    {
        $this->factory = $this->factory->withDefaultSchemaClasses([
            Schema::REPOSITORY => UserRepository::class,
        ]);

        $mapper = $this->factory->repository($this->orm, $this->orm->getSchema(), 'user', new Select($this->orm, 'user'));

        self::assertInstanceOf(UserRepository::class, $mapper);
    }

    public function testShouldThrowExceptionIfDefaultRepositoryClassNotImplementRepositoryInterface()
    {
        $this->factory = $this->factory->withDefaultSchemaClasses([
            Schema::REPOSITORY => User::class,
        ]);

        self::expectException(TypecastException::class);

        $this->factory->repository($this->orm, $this->orm->getSchema(), 'user', new Select($this->orm, 'user'));
    }

    public function testShouldMakeDefaultSource()
    {
        $result = $this->factory->source($this->orm, $this->orm->getSchema(), 'user');

        self::assertInstanceOf(Source::class, $result);
    }

    public function testShouldChangeDefaultSourceClass()
    {
        $this->factory = $this->factory->withDefaultSchemaClasses([
            Schema::SOURCE => DifferentSource::class,
        ]);

        $result = $this->factory->source($this->orm, $this->orm->getSchema(), 'user');

        self::assertInstanceOf(DifferentSource::class, $result);
    }

    public function testShouldThrowExceptionIfDefaultSourceClassNotImplementSourceInterface()
    {
        $this->factory = $this->factory->withDefaultSchemaClasses([
            Schema::SOURCE => User::class,
        ]);

        self::expectException(TypecastException::class);

        $this->factory->source($this->orm, $this->orm->getSchema(), 'user');
    }
}
