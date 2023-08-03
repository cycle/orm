<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Functional\Driver\Common\Mapper\ProxyEntityMapper;

use Cycle\ORM\Collection\Pivoted\PivotedCollection;
use Cycle\ORM\Mapper\Mapper;
use Cycle\ORM\Reference\ReferenceInterface;
use Cycle\ORM\Relation;
use Cycle\ORM\Schema;
use Cycle\ORM\Select;
use Cycle\ORM\Tests\Functional\Driver\Common\Mapper\BaseMapperTest;
use Cycle\ORM\Tests\Traits\TableTrait;
use PHPUnit\Framework\AssertionFailedError;

class EntityWithRelationHydrationTest extends BaseMapperTest
{
    use TableTrait;

    public const DRIVER = 'sqlite';

    public function setUp(): void
    {
        parent::setUp();

        $this->makeTable('user', ['id' => 'primary', 'email' => 'string', 'friend_id' => 'integer,nullable']);
        $this->makeTable('profile', ['id' => 'primary', 'name' => 'string', 'user_id' => 'integer']);
        $this->makeTable('tag', ['id' => 'primary', 'name' => 'string',]);
        $this->makeTable('tag_user', ['user_id' => 'integer', 'tag_id' => 'integer',]);
        $this->makeTable('avatar', ['id' => 'primary', 'parent_id' => 'integer,nullable', 'parent_type' => 'string,nullable', 'url' => 'string',]);

        $this->getDatabase()->table('user')->insertMultiple(
            ['email'],
            [['hello@world.com'],]
        );

        $this->getDatabase()->table('profile')->insertMultiple(
            ['user_id', 'name'],
            [[1, 'John Smith'],]
        );

        $this->getDatabase()->table('tag')->insertMultiple(
            ['name'],
            [['tag a'], ['tag b'], ['tag c'],]
        );

        $this->orm = $this->withSchema(new Schema([
            EntityWithRelationHydrationTag::class => [
                Schema::ROLE => 'tag',
                Schema::MAPPER => Mapper::class,
                Schema::DATABASE => 'default',
                Schema::TABLE => 'tag',
                Schema::PRIMARY_KEY => 'id',
                Schema::COLUMNS => ['id', 'name'],
                Schema::TYPECAST => ['id' => 'int'],
                Schema::SCHEMA => [],
                Schema::RELATIONS => [
                    'users' => [
                        Relation::TYPE => Relation::MANY_TO_MANY,
                        Relation::TARGET => EntityWithRelationHydrationUser::class,
                        Relation::SCHEMA => [
                            Relation::CASCADE => true,
                            Relation::THROUGH_ENTITY => EntityWithRelationHydrationTagContext::class,
                            Relation::INNER_KEY => 'id',
                            Relation::OUTER_KEY => 'id',
                            Relation::THROUGH_INNER_KEY => 'tag_id',
                            Relation::THROUGH_OUTER_KEY => 'user_id',
                        ],
                    ],
                ],
            ],
            EntityWithRelationHydrationTagContext::class => [
                Schema::ROLE => 'tag_context',
                Schema::MAPPER => Mapper::class,
                Schema::DATABASE => 'default',
                Schema::TABLE => 'tag_user',
                Schema::PRIMARY_KEY => 'id',
                Schema::COLUMNS => ['user_id', 'tag_id'],
                Schema::TYPECAST => [ 'user_id' => 'int', 'tag_id' => 'int'],
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
                    'tag' => [
                        Relation::TYPE => Relation::BELONGS_TO,
                        Relation::TARGET => EntityWithRelationHydrationTag::class,
                        Relation::SCHEMA => [
                            Relation::CASCADE => true,
                            Relation::INNER_KEY => 'tag_id',
                            Relation::OUTER_KEY => 'id',
                        ],
                    ],
                ],
            ],
            EntityWithRelationHydrationUser::class => [
                Schema::ROLE => 'user',
                Schema::MAPPER => Mapper::class,
                Schema::DATABASE => 'default',
                Schema::TABLE => 'user',
                Schema::PRIMARY_KEY => 'id',
                Schema::COLUMNS => ['id', 'email'],
                Schema::SCHEMA => [],
                Schema::RELATIONS => [
                    'tags' => [
                        Relation::TYPE => Relation::MANY_TO_MANY,
                        Relation::TARGET => EntityWithRelationHydrationTag::class,
                        Relation::SCHEMA => [
                            Relation::CASCADE => true,
                            Relation::THROUGH_ENTITY => EntityWithRelationHydrationTagContext::class,
                            Relation::INNER_KEY => 'id',
                            Relation::OUTER_KEY => 'id',
                            Relation::THROUGH_INNER_KEY => 'user_id',
                            Relation::THROUGH_OUTER_KEY => 'tag_id',
                        ],
                    ],
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
                    'avatar' => [
                        Relation::TYPE => Relation::MORPHED_HAS_ONE,
                        Relation::TARGET => EntityWithRelationHydrationImage::class,
                        Relation::SCHEMA => [
                            Relation::CASCADE => true,
                            Relation::INNER_KEY => 'id',
                            Relation::OUTER_KEY => 'parentId',
                            Relation::MORPH_KEY => 'parentType',
                        ],
                    ],
                    'user' => [
                        Relation::TYPE => Relation::BELONGS_TO,
                        Relation::TARGET => EntityWithRelationHydrationUser::class,
                        Relation::SCHEMA => [
                            Relation::CASCADE => true,
                            Relation::INNER_KEY => 'user_id',
                            Relation::OUTER_KEY => 'id',
                        ],
                    ],
                    'refers_user' => [
                        Relation::TYPE => Relation::REFERS_TO,
                        Relation::TARGET => EntityWithRelationHydrationUser::class,
                        Relation::SCHEMA => [
                            Relation::CASCADE => true,
                            Relation::INNER_KEY => 'user_id',
                            Relation::OUTER_KEY => 'id',
                        ],
                    ],
                ],
            ],
            EntityWithRelationHydrationImage::class => [
                Schema::ROLE => 'avatar',
                Schema::MAPPER => Mapper::class,
                Schema::DATABASE => 'default',
                Schema::TABLE => 'avatar',
                Schema::PRIMARY_KEY => 'id',
                Schema::COLUMNS => [
                    'id' => 'id',
                    'parentId' => 'parent_id',
                    'parentType' => 'parent_type',
                    'url' => 'url',
                ],
                Schema::SCHEMA => [],
                Schema::RELATIONS => [
                    'parent' => [
                        Relation::TYPE => Relation::BELONGS_TO_MORPHED,
                        Relation::TARGET => EntityWithRelationHydrationProfile::class,
                        Relation::LOAD => Relation::LOAD_PROMISE,

                        Relation::SCHEMA => [
                            Relation::NULLABLE => true,
                            Relation::CASCADE => true,
                            Relation::OUTER_KEY => 'id',
                            Relation::INNER_KEY => 'parentId',
                            Relation::MORPH_KEY => 'parentType',
                        ],
                    ],
                ],
            ],
            EntityWithMixedTypeRelation::class => [
                Schema::ROLE => 'mixed_type',
                Schema::MAPPER => Mapper::class,
                Schema::DATABASE => 'default',
                Schema::TABLE => 'user',
                Schema::PRIMARY_KEY => 'id',
                Schema::COLUMNS => [
                    'id' => 'id',
                    'email' => 'email',
                    'friend_id' => 'friend_id',
                ],
                Schema::TYPECAST => ['id' => 'int', 'friend_id' => 'int'],
                Schema::SCHEMA => [],
                Schema::RELATIONS => [
                    'friend' => [
                        Relation::TYPE => Relation::BELONGS_TO,
                        Relation::TARGET => EntityWithMixedTypeRelation::class,
                        Relation::LOAD => Relation::LOAD_PROMISE,

                        Relation::SCHEMA => [
                            Relation::NULLABLE => false,
                            Relation::CASCADE => true,
                            Relation::OUTER_KEY => 'id',
                            Relation::INNER_KEY => 'friend_id',
                        ],
                    ],
                ],
            ],
        ]));
    }

    public function testPrivateBelongsToRelationPropertyWithoutProxyShouldBeFilled(): void
    {
        $profile = new EntityWithRelationHydrationProfile('test');
        $profile->user_id = 1;

        $this->save($profile);
        $this->assertEquals(1, $profile->getUser()->id);
        // todo should be check?
        // $this->assertInstanceOf(ReferenceInterface::class, $profile->getRefersUser());
    }

    public function testRelationWithMixedTypeShouldBeFilledAsReference(): void
    {
        $user = new EntityWithMixedTypeRelation();
        $user->email = 'foo@bar.com';
        $user->friend_id = 1;

        $this->save($user);

        $this->assertInstanceOf(ReferenceInterface::class, $user->friend);
    }

    public function testRelationExistedInHeapMustFilledAsEntity(): void
    {
        $user = new EntityWithMixedTypeRelation();
        $user->email = 'foo@bar.com';
        $user->friend_id = 1;

        $this->orm->getRepository(EntityWithMixedTypeRelation::class)->findByPK(1);

        $this->save($user);
        $this->assertInstanceOf(EntityWithMixedTypeRelation::class, $user->friend);
    }

    public function testPrivateHasManyRelationPropertyWithoutProxyShouldBeFilled(): void
    {
        $profile = new EntityWithRelationHydrationProfile('test');
        $user = new EntityWithRelationHydrationUser('admin@site.com');
        $user->profiles[] = $profile;

        $this->save($user);

        $this->assertSame($user, $profile->getUser());
    }

    public function testPrivateManyToManyRelationPropertyWithoutProxyShouldBeFilled(): void
    {
        $tagContext = new EntityWithRelationHydrationTagContext();
        $tagContext->user_id = 1;
        $tagContext->tag_id = 2;

        $this->save($tagContext);

        $this->assertInstanceOf(ReferenceInterface::class, $tagContext->getTag());
        $this->assertInstanceOf(ReferenceInterface::class, $tagContext->getUser());
    }

    /**
     * TODO: error with shadow belongs to
     */
    public function testPrivateMorphBelongsToRelationPropertyWithoutProxyShouldBeFilled(): void
    {
        $profile = new EntityWithRelationHydrationProfile('test');
        $profile->user_id = 1;

        $avatar = new EntityWithRelationHydrationImage();
        $avatar->url = 'http://site.com';
        $avatar->setParent($profile);

        $this->save($avatar);
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
        } catch (AssertionFailedError $e) {
            throw $e;
        } catch (\Exception $e) {
            $this->stringStartsWith('Indirect modification of overloaded property')->evaluate($e->getMessage());
            // That's OK
        }

        // $user->profile now loaded
        $user->profiles[] = 'test-value';
        $this->assertContains('test-value', $user->profiles);
    }

    public function testGetLinkValueFromLazyOverloadedRelation(): void
    {
        $user = (new Select($this->orm, EntityWithRelationHydrationUser::class))
            ->fetchOne();

        try {
            $collection = &$user->profiles;
            $this->fail('There should be error (notice) thrown "Indirect modification of overloaded property"');
        } catch (AssertionFailedError $e) {
            throw $e;
        } catch (\Exception $e) {
            $this->stringStartsWith('Indirect modification of overloaded property')->evaluate($e->getMessage());
            // That's OK
        }
        // $user->profile now loaded
        $collection = &$user->profiles;
        $collection[] = 'test-value';
        $this->assertContains('test-value', $user->profiles);
    }
}

class EntityWithRelationHydrationUser
{
    public $id;
    public array $profiles = [];
    private EntityWithRelationHydrationProfile $profile;
    private $email;

    public function __construct(string $email)
    {
        $this->email = $email;
    }

    public function getEmail()
    {
        return $this->email;
    }

    public function setProfile(EntityWithRelationHydrationProfile $profile)
    {
        $this->profile = $profile;
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
    private object $refers_user;
    private EntityWithRelationHydrationImage $avatar;
    public $user_id;

    public function __construct(string $name)
    {
        $this->name = $name;
    }

    public function getUser()
    {
        return $this->user;
    }

    public function getAvatar()
    {
        return $this->avatar;
    }

    public function getRefersUser(): object
    {
        return $this->refers_user;
    }

    public function __set(string $name, $value): void
    {
        throw new \Exception('Value shouldn\'t hydrate via __set method.');
    }
}

class EntityWithRelationHydrationTag
{
    public $name;
    public $users;

    public function __construct()
    {
        $this->users = new PivotedCollection();
    }
}


class EntityWithRelationHydrationTagContext
{
    private $user;
    private ReferenceInterface $tag;

    public $tag_id;
    public $user_id;

    public function getTag(): ReferenceInterface
    {
        return $this->tag;
    }

    public function getUser(): ReferenceInterface
    {
        return $this->user;
    }
}


class EntityWithRelationHydrationImage
{
    private mixed $parent;
    public $url;

    public function setParent($parent): void
    {
        $this->parent = $parent;
    }
}

class EntityWithMixedTypeRelation
{
    public int $id;
    public string $email;
    public ?int $friend_id = null;
    public mixed $friend;
}
