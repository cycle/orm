<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Functional\Driver\Common\Mapper\ProxyEntityMapper;

use Cycle\ORM\Mapper\Mapper;
use Cycle\ORM\Relation;
use Cycle\ORM\Schema;
use Cycle\ORM\Select;
use Cycle\ORM\Tests\Functional\Driver\Common\Mapper\BaseMapperTest;
use Cycle\ORM\Tests\Traits\TableTrait;

class EntityWithRelationHydrationTest extends BaseMapperTest
{
    use TableTrait;

    public const DRIVER = 'sqlite';

    public function setUp(): void
    {
        parent::setUp();

        $this->makeTable('user', ['id' => 'primary', 'email' => 'string']);
        $this->makeTable('profile', ['id' => 'primary', 'name' => 'string', 'user_id' => 'integer']);

        $this->getDatabase()->table('user')->insertMultiple(
            ['email'],
            [['hello@world.com'],]
        );

        $this->getDatabase()->table('profile')->insertMultiple(
            ['user_id', 'name'],
            [[1, 'John Smith'],]
        );

        $this->orm = $this->withSchema(new Schema([
            EntityWithRelationHydrationUser::class => [
                Schema::ROLE => 'user',
                Schema::MAPPER => Mapper::class,
                Schema::DATABASE => 'default',
                Schema::TABLE => 'user',
                Schema::PRIMARY_KEY => 'id',
                Schema::COLUMNS => ['id', 'email'],
                Schema::SCHEMA => [],
                Schema::RELATIONS => [
                    'profile' => [
                        Relation::TYPE => Relation::HAS_ONE,
                        Relation::TARGET => EntityWithRelationHydrationProfile::class,
                        Relation::SCHEMA => [
                            Relation::CASCADE => true,
                            Relation::INNER_KEY => 'id',
                            Relation::OUTER_KEY => 'user_id',
                        ],
                    ],
                    'profiles' => [
                        Relation::TYPE => Relation::HAS_MANY,
                        Relation::TARGET => EntityWithRelationHydrationProfile::class,
                        Relation::COLLECTION_TYPE => 'array',
                        Relation::SCHEMA => [
                            Relation::CASCADE => true,
                            Relation::INNER_KEY => 'id',
                            Relation::OUTER_KEY => 'user_id',
                        ],
                    ],
                ],
            ],
            EntityWithRelationHydrationProfile::class => [
                Schema::ROLE => 'profile',
                Schema::MAPPER => Mapper::class,
                Schema::DATABASE => 'default',
                Schema::TABLE => 'profile',
                Schema::PRIMARY_KEY => 'id',
                Schema::COLUMNS => ['id', 'user_id', 'name'],
                Schema::SCHEMA => [],
                Schema::RELATIONS => [
                    'user' => [
                        Relation::TYPE => Relation::BELONGS_TO,
                        Relation::TARGET => EntityWithRelationHydrationUser::class,
                        Relation::SCHEMA => [
                            Relation::CASCADE => true,
                            Relation::INNER_KEY => 'user_id',
                            Relation::OUTER_KEY => 'id',
                        ],
                    ],
                ],
            ],
        ]));
    }

    public function testPrivateRelationPropertyShouldBeFilled(): void
    {
        $selector = new Select($this->orm, EntityWithRelationHydrationProfile::class);

        $profile = $selector
            ->load('user.profile')
            ->fetchOne();

        $this->assertEquals('1', $profile->getUser()->id);
        $this->assertEquals('hello@world.com', $profile->getUser()->getEmail());
        $this->assertSame($profile, $profile->getUser()->getProfile());
    }

    public function testLazyLoad(): void
    {
        $selector = new Select($this->orm, EntityWithRelationHydrationProfile::class);

        $profile = $selector
            ->fetchOne();

        $this->assertEquals('1', $profile->getUser()->id);
        $this->assertEquals('hello@world.com', $profile->getUser()->getEmail());
        $this->assertSame($profile, $profile->getUser()->getProfile());
    }

    public function testChangeLazyOverloadedArray(): void
    {
        $user = (new Select($this->orm, EntityWithRelationHydrationUser::class))
            ->fetchOne();

        try {
            $user->profiles[] = 'test-value';
            $this->fail('There should be error (notice) thrown "Indirect modification of overloaded property"');
        } catch (\Exception) {
            // That's OK
        }
        // $user->profile now loaded
        $user->profiles[] = 'test-value';
        $this->assertContains('test-value', $user->profiles);
    }

    public function testWriteByLinkLazyOverloadedArray()
    {
        $user = (new Select($this->orm, EntityWithRelationHydrationUser::class))
            ->fetchOne();

        try {
            $collection = &$user->profiles;
            $this->fail('There should be error (notice) thrown "Indirect modification of overloaded property"');
        } catch (\Exception) {
            // That's OK
        }
        // $user->profile now loaded
        $collection = &$user->profiles;
        $collection[] = 'test-value';
        $this->assertContains('test-value', $user->profiles);
    }
}
// phpcs:disable
class EntityWithRelationHydrationUser
{
    public $id;
    public array $profiles = [];
    protected EntityWithRelationHydrationProfile $profile;
    private $email;

    public function getEmail()
    {
        return $this->email;
    }

    public function getProfile(): EntityWithRelationHydrationProfile
    {
        return $this->profile;
    }
}

class EntityWithRelationHydrationProfile
{
    public $id;
    private $name;
    private EntityWithRelationHydrationUser $user;

    public function getUser()
    {
        return $this->user;
    }
}
// phpcs:enable
