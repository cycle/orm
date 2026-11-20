<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Feature\SQLite\Mapper\ProxyEntityMapper;

use Cycle\ORM\Mapper\Mapper;
use Cycle\ORM\Relation;
use Cycle\ORM\Schema;
use Cycle\ORM\Tests\Feature\Common\Mapper\BaseMapperTest;

class EntityWithRelationCreationTest extends BaseMapperTest
{
    public const DRIVER = 'sqlite';

    #[\Testo\Test]
    public function testProxyEntityRelationPropertiesShouldBeUnsetAfterCreation(): void
    {
        $mapper = $this->orm->getMapper(EntityWithRelationCreationUser::class);

        $emptyObject = $mapper->init([]);

        $refl = new \ReflectionClass(EntityWithRelationCreationAbstractUser::class);

        $profileProperty = $refl->getProperty('profile');
        PHP_VERSION_ID < 80100 and $profileProperty->setAccessible(true);

        \Testo\Assert::false($profileProperty->isInitialized($emptyObject));
        \Testo\Assert::equals($emptyObject->id, 123);
        \Testo\Assert::equals($emptyObject->getUsername(), 'test');
        \Testo\Assert::equals($emptyObject->getEmail(), 'test@site.com');
    }

    #[\Testo\Lifecycle\BeforeTest]
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
                ],
            ),
        );
    }
}

// phpcs:disable
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
// phpcs:enable
