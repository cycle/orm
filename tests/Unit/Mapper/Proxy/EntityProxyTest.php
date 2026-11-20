<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Unit\Mapper\Proxy;

use Cycle\ORM\FactoryInterface;
use Cycle\ORM\Mapper\Proxy\Hydrator\ClassPropertiesExtractor;
use Cycle\ORM\Mapper\Proxy\Hydrator\ClosureHydrator;
use Cycle\ORM\Mapper\Proxy\ProxyEntityFactory;
use Cycle\ORM\ORMInterface;
use Cycle\ORM\Relation\ActiveRelationInterface;
use Cycle\ORM\RelationMap;
use Cycle\ORM\SchemaInterface;
use Mockery as m;
use Testo\Assert;
use Testo\Expect;
use Testo\Test;

#[Test]
final class EntityProxyTest
{
    public function unsetPrivateRelationDropsItsValue(): never
    {
        $proxy = $this->proxy();
        $proxy->owner = 'alice';
        Assert::same($proxy->getOwner(), 'alice');

        unset($proxy->owner);

        Expect::exception(\RuntimeException::class)->withMessageContaining('is not initialized');
        $proxy->getOwner();
    }

    public function magicFieldIsHandledByEntitySetter(): void
    {
        $proxy = $this->proxy();

        $proxy->nickname = 'bob';

        Assert::same($proxy->nickname, 'BOB');
    }

    private function proxy(): ProxiedNote
    {
        $schema = m::mock(SchemaInterface::class);
        $schema->allows('getOuterRelations')->andReturn([]);
        $schema->allows('getInnerRelations')->andReturn(['owner' => []]);
        $schema->allows('define')->andReturn(null);
        $factory = m::mock(FactoryInterface::class);
        $factory->allows('relation')->andReturn(m::mock(ActiveRelationInterface::class));
        $orm = m::mock(ORMInterface::class);
        $orm->allows('getSchema')->andReturn($schema);
        $orm->allows('getFactory')->andReturn($factory);

        $proxyFactory = new ProxyEntityFactory(new ClosureHydrator(), new ClassPropertiesExtractor());
        return $proxyFactory->create(RelationMap::build($orm, 'note'), ProxiedNote::class);
    }
}

// phpcs:disable
class ProxiedNote
{
    private $owner;
    private array $magic = [];

    public function getOwner()
    {
        return $this->owner;
    }

    public function __get(string $name)
    {
        return $this->magic[$name] ?? null;
    }

    public function __set(string $name, $value): void
    {
        $this->magic[$name] = \strtoupper($value);
    }
}
// phpcs:enable
