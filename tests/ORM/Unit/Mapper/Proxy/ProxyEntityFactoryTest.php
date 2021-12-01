<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Unit\Mapper\Proxy;

use Cycle\ORM\FactoryInterface;
use Cycle\ORM\Mapper\Mapper;
use Cycle\ORM\Mapper\Proxy\Hydrator\ClassPropertiesExtractor;
use Cycle\ORM\Mapper\Proxy\Hydrator\ClosureHydrator;
use Cycle\ORM\Mapper\Proxy\ProxyEntityFactory;
use Cycle\ORM\Mapper\Proxy\ProxyEntityInterface;
use Cycle\ORM\ORM;
use Cycle\ORM\RelationMap;
use Cycle\ORM\Schema;
use Cycle\ORM\SchemaInterface;
use Cycle\ORM\Tests\Fixtures\User;
use PHPUnit\Framework\TestCase;

class ProxyEntityFactoryTest extends TestCase
{
    private ProxyEntityFactory $factory;
    private ORM $orm;

    protected function setUp(): void
    {
        parent::setUp();

        $this->factory = new ProxyEntityFactory(
            new ClosureHydrator(),
            new ClassPropertiesExtractor()
        );

        $factory = $this->createMock(FactoryInterface::class);

        $this->orm = new ORM(
            $factory, new Schema([
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
            ])
        );
    }

    public function testCreatesObject()
    {
        $user = $this->factory->create(RelationMap::build($this->orm, 'user'), User::class, [
            'id' => 1,
            'email' => 'test@site.com',
        ]);

        $this->assertInstanceOf(ProxyEntityInterface::class, $user);
        $this->assertSame(1, $user->id);
        $this->assertSame('test@site.com', $user->email);
        $this->assertNull($user->balance);
    }
}
