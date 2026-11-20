<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Unit\Mapper\Proxy;

use Cycle\ORM\EntityProxyInterface;
use Cycle\ORM\FactoryInterface;
use Cycle\ORM\Mapper\Mapper;
use Cycle\ORM\Mapper\Proxy\Hydrator\ClassPropertiesExtractor;
use Cycle\ORM\Mapper\Proxy\Hydrator\ClosureHydrator;
use Cycle\ORM\Mapper\Proxy\ProxyEntityFactory;
use Cycle\ORM\ORM;
use Cycle\ORM\ORMInterface;
use Cycle\ORM\Relation\ActiveRelationInterface;
use Cycle\ORM\RelationMap;
use Cycle\ORM\Schema;
use Cycle\ORM\SchemaInterface;
use Cycle\ORM\Tests\Fixtures\User;
use Testo\Assert;
use Testo\Expect;
use Testo\Lifecycle\BeforeTest;
use Testo\Test;

#[Test]
final class ProxyEntityFactoryTest
{
    private ProxyEntityFactory $factory;
    private ORM $orm;

    public function testCreatesObject(): void
    {
        $user = $this->factory->create(RelationMap::build($this->orm, 'user'), User::class);

        Assert::instanceOf($user, EntityProxyInterface::class);
    }

    public function testCreatesNonExistingObjectShouldThrowException(): void
    {
        Expect::exception(\RuntimeException::class)->withMessageContaining('The entity `hello-world` class does not exist. Proxy factory can not create classless entities.');

        $this->factory->create(RelationMap::build($this->orm, 'user'), 'hello-world');
    }

    public function finalEntityClassCannotBeProxied(): never
    {
        Expect::exception(\RuntimeException::class)->withMessage(
            \sprintf('The entity `%s` class is final and can\'t be extended.', FinalNote::class),
        );

        $this->factory->create(RelationMap::build($this->orm, 'user'), FinalNote::class);
    }

    public function entityClassWithoutNamespaceIsProxied(): void
    {
        $entity = $this->factory->create(RelationMap::build($this->orm, 'user'), \stdClass::class);

        Assert::instanceOf($entity, \stdClass::class);
        Assert::instanceOf($entity, EntityProxyInterface::class);
        Assert::same(\get_parent_class($entity), \stdClass::class);
    }

    public function testExtractRelationsOfNotProxiedEntityReturnsOnlyRelations(): void
    {
        $schema = \Mockery::mock(SchemaInterface::class);
        $schema->allows('getOuterRelations')->andReturn([]);
        $schema->allows('getInnerRelations')->andReturn(['profile' => []]);
        $schema->allows('define')->andReturn(null);
        $factory = \Mockery::mock(FactoryInterface::class);
        $factory->allows('relation')->andReturn(\Mockery::mock(ActiveRelationInterface::class));
        $orm = \Mockery::mock(ORMInterface::class);
        $orm->allows('getSchema')->andReturn($schema);
        $orm->allows('getFactory')->andReturn($factory);
        $user = new User();
        $user->email = 'foo@bar';
        $user->profile = 'profile';

        $relations = $this->factory->extractRelations(RelationMap::build($orm, 'user'), $user);

        Assert::same($relations, ['profile' => 'profile']);
    }

    #[BeforeTest]
    protected function setUp(): void
    {
        $this->factory = new ProxyEntityFactory(
            new ClosureHydrator(),
            new ClassPropertiesExtractor(),
        );

        $factory = \Mockery::mock(FactoryInterface::class)->shouldIgnoreMissing();

        $this->orm = new ORM(
            $factory,
            new Schema([
                'user' => [
                    SchemaInterface::ENTITY => User::class,
                    SchemaInterface::MAPPER => Mapper::class,
                    SchemaInterface::DATABASE => 'default',
                    SchemaInterface::TABLE => 'user',
                    SchemaInterface::PRIMARY_KEY => 'id',
                    SchemaInterface::COLUMNS => ['id', 'email', 'balance'],
                    SchemaInterface::SCHEMA => [],
                    SchemaInterface::RELATIONS => [],
                ],
            ]),
        );
    }
}

// phpcs:disable
final class FinalNote {}
// phpcs:enable
