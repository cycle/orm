<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Functional\Mapper\ProxyEntityMapper;

use Cycle\ORM\Mapper\Mapper;
use Cycle\ORM\Relation;
use Cycle\ORM\Schema;
use Cycle\ORM\Tests\Functional\Mapper\BaseMapperTest;
use ReflectionClass;

class EntityWithRelationCreationTest extends BaseMapperTest
{
    public const DRIVER = 'sqlite';

    public function setUp(): void
    {
        parent::setUp();

        $this->orm = $this->withSchema(
            new Schema(
                [
                    EntityWithRelationCreationUser::class => [
                        Schema::MAPPER => Mapper::class,
                        Schema::DATABASE => 'default',
                        Schema::TABLE => 'user',
                        Schema::PRIMARY_KEY => 'uuid',
                        Schema::COLUMNS => ['id', 'username', 'email', 'isVerified', 'profileId'],
                        Schema::TYPECAST => [],
                        Schema::SCHEMA => [],
                        Schema::RELATIONS => [
                            'profile' => [
                                Relation::TYPE => Relation::HAS_ONE,
                                Relation::TARGET => EntityWithRelationCreationProfile::class,
                                Relation::SCHEMA => [
                                    Relation::CASCADE => true,
                                    Relation::INNER_KEY => 'id',
                                    Relation::OUTER_KEY => 'user_id',
                                ],
                            ],
                        ],
                    ],
                ]
            )
        );
    }

    public function testProxyEntityRelationPropertiesShouldBeUnsetAfterCreation()
    {
        $mapper = $this->orm->getMapper(EntityWithRelationCreationUser::class);

        $emptyObject = $mapper->init([]);

        $refl = new ReflectionClass(EntityWithRelationCreationAbstractUser::class);

        $profileProperty = $refl->getProperty('profile');
        $profileProperty->setAccessible(true);

        $this->assertFalse($profileProperty->isInitialized($emptyObject));
        $this->assertEquals(123, $emptyObject->id);
        $this->assertEquals('test', $emptyObject->getUsername());
        $this->assertEquals('test@site.com', $emptyObject->getEmail());
    }
}

class EntityWithRelationCreationAbstractUser
{
    private $profile = 123;
}

class EntityWithRelationCreationUser extends EntityWithRelationCreationAbstractUser
{
    public int $id = 123;
    protected string $username = 'test';
    private string $email = 'test@site.com';

    public function getUsername(): string
    {
        return $this->username;
    }

    public function getEmail(): string
    {
        return $this->email;
    }
}

class EntityWithRelationCreationProfile
{
    public int $id = 123;
}
