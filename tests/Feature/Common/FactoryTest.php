<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Feature\Common;

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
use Cycle\Testing\ORM\OrmTestCase;
use Testo\Assert;
use Testo\Expect;
use Testo\Lifecycle\BeforeTest;
use Testo\Test;

final class FactoryTest extends OrmTestCase
{
    /**
     * @var Factory
     */
    private $factory;

    #[Test]
    public function testShouldMakeDefaultMapper(): void
    {
        $mapper = $this->factory->mapper($this->orm, 'user');

        Assert::instanceOf($mapper, Mapper::class);
    }

    #[Test]
    public function testShouldChangeDefaultMapperClass(): void
    {
        $this->factory = $this->factory->withDefaultSchemaClasses([
            Schema::MAPPER => TimestampedMapper::class,
        ]);

        $mapper = $this->factory->mapper($this->orm, 'user');

        Assert::instanceOf($mapper, TimestampedMapper::class);
    }

    #[Test]
    public function testShouldThrowExceptionIfDefaultMapperClassNotImplementMapperInterface(): void
    {
        $this->factory = $this->factory->withDefaultSchemaClasses([
            Schema::MAPPER => User::class,
        ]);

        Expect::exception(TypecastException::class);

        $this->factory->mapper($this->orm, 'user');
    }

    #[Test]
    public function testShouldMakeDefaultRepository(): void
    {
        $result = $this->factory->repository(
            $this->orm,
            $this->orm->getSchema(),
            'user',
            new Select($this->orm, 'user'),
        );

        Assert::instanceOf($result, Repository::class);
    }

    #[Test]
    public function testShouldChangeDefaultRepositoryClass(): void
    {
        $this->factory = $this->factory->withDefaultSchemaClasses([
            Schema::REPOSITORY => UserRepository::class,
        ]);

        $result = $this->factory->repository(
            $this->orm,
            $this->orm->getSchema(),
            'user',
            new Select($this->orm, 'user'),
        );

        Assert::instanceOf($result, UserRepository::class);
    }

    #[Test]
    public function testShouldThrowExceptionIfDefaultRepositoryClassNotImplementRepositoryInterface(): void
    {
        $this->factory = $this->factory->withDefaultSchemaClasses([
            Schema::REPOSITORY => User::class,
        ]);

        Expect::exception(TypecastException::class);

        $this->factory->repository($this->orm, $this->orm->getSchema(), 'user', new Select($this->orm, 'user'));
    }

    #[Test]
    public function testShouldMakeDefaultSource(): void
    {
        $result = $this->factory->source($this->orm->getSchema(), 'user');

        Assert::instanceOf($result, Source::class);
    }

    #[Test]
    public function testShouldChangeDefaultSourceClass(): void
    {
        $this->factory = $this->factory->withDefaultSchemaClasses([
            Schema::SOURCE => DifferentSource::class,
        ]);

        $result = $this->factory->source($this->orm->getSchema(), 'user');

        Assert::instanceOf($result, DifferentSource::class);
    }

    #[Test]
    public function testShouldThrowExceptionIfDefaultSourceClassNotImplementSourceInterface(): void
    {
        $this->factory = $this->factory->withDefaultSchemaClasses([
            Schema::SOURCE => User::class,
        ]);

        Expect::exception(TypecastException::class);

        $this->factory->source($this->orm->getSchema(), 'user');
    }

    #[BeforeTest]
    public function setUp(): void
    {
        $this->orm = $this->withSchema(new Schema([
            User::class => [
                Schema::ROLE => 'user',
                Schema::DATABASE => 'default',
                Schema::TABLE => 'user',
                Schema::PRIMARY_KEY => 'id',
                Schema::COLUMNS => ['id', 'email', 'balance'],
                Schema::SCHEMA => [],
                Schema::RELATIONS => [],
            ],
        ]));

        $this->factory = new Factory($this->dbal);
    }
}
