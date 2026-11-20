<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Feature\SQLite\Mapper\ProxyEntityMapper;

use Cycle\ORM\Exception\MapperException;
use Cycle\ORM\Mapper\Mapper;
use Cycle\ORM\Schema;
use Cycle\ORM\SchemaInterface;
use Cycle\Testing\ORM\OrmTestCase;

class EntityHydrationTest extends OrmTestCase
{
    #[\Testo\Test]
    public function testDataForBaseClassShouldBeExtracted(): void
    {
        $user = new EntityHydrationUser(123, 'guest', 'guest@site.com');

        $mapper = $this->orm->getMapper($user);

        \Testo\Assert::equals($mapper->extract($user), [
            'id' => 123,
            'username' => 'guest',
            'email' => 'guest@site.com',
            'attributes' => [],
        ]);
    }

    #[\Testo\Test]
    public function testDataForExtendedClassShouldBeExtracted(): void
    {
        $user = new ExtendedEntityHydrationUser(123, 'guest', 'guest@site.com', true, 234);

        $mapper = $this->orm->getMapper($user);

        \Testo\Assert::equals($mapper->extract($user), [
            'id' => 123,
            'username' => 'guest',
            'email' => 'guest@site.com',
            'isVerified' => true,
            'profileId' => 234,
            'attributes' => [],
        ]);
    }

    #[\Testo\Test]
    public function testDataShouldBeHydratedToBaseClass(): void
    {
        $mapper = $this->orm->getMapper(EntityHydrationUser::class);

        $emptyObject = $mapper->init([]);
        \Testo\Assert::instanceOf($emptyObject, EntityHydrationUser::class);

        $user = $mapper->hydrate($emptyObject, [
            'id' => 123,
            'username' => 'guest',
            'email' => 'guest@site.com',
        ]);

        \Testo\Assert::same($user->getId(), 123);
        \Testo\Assert::same($user->getUsername(), 'guest');
        \Testo\Assert::same($user->getEmail(), 'guest@site.com');
    }

    #[\Testo\Test]
    public function testDataShouldBeHydratedToExtendedClass(): void
    {
        $mapper = $this->orm->getMapper(ExtendedEntityHydrationUser::class);

        $emptyObject = $mapper->init([]);
        \Testo\Assert::instanceOf($emptyObject, ExtendedEntityHydrationUser::class);

        $user = $mapper->hydrate($emptyObject, [
            'id' => 123,
            'username' => 'guest',
            'email' => 'guest@site.com',
            'isVerified' => true,
            'profileId' => 234,
        ]);

        \Testo\Assert::same($user->getId(), 123);
        \Testo\Assert::same($user->getUsername(), 'guest');
        \Testo\Assert::same($user->getEmail(), 'guest@site.com');
        \Testo\Assert::same($user->isVerified(), true);
        \Testo\Assert::same($user->getProfileId(), 234);
    }

    #[\Testo\Test]
    public function testUndefinedPropertiesShouldBePassedThroughSetter(): void
    {
        $mapper = $this->orm->getMapper(EntityHydrationUser::class);

        $emptyObject = $mapper->init([]);

        $user = $mapper->hydrate($emptyObject, [
            'id' => 123,
            'tag' => 'test',
            'username' => 'guest',
            'email' => 'guest@site.com',
        ]);

        \Testo\Assert::equals($user->getAttributes(), ['tag' => 'test']);
    }

    #[\Testo\Test]
    public function testRequestedUndefinedPropertiesShouldBePassedThroughGetter(): void
    {
        $mapper = $this->orm->getMapper(EntityHydrationUser::class);

        $emptyObject = $mapper->init([]);

        $user = $mapper->hydrate($emptyObject, [
            'id' => 123,
            'tag' => 'test',
            'username' => 'guest',
            'email' => 'guest@site.com',
        ]);

        \Testo\Assert::equals($user->tag, 'test');
    }

    #[\Testo\Test]
    public function testWriteDynamicProperty(): void
    {
        $mapper = $this->orm->getMapper(EntityHydrationIdentityUser::class);
        $emptyObject = new EntityHydrationIdentityUser();

        $mapper->hydrate($emptyObject, ['id' => 123, 'value' => 'test']);

        // self::assertSame(['id' => 123], $mapper->fetchFields($emptyObject));
        \Testo\Assert::true(true, 'No exception thrown');
    }

    #[\Testo\Test]
    public function testHydrateBadType(): void
    {
        $mapper = $this->orm->getMapper(EntityHydrationIdentityUser::class);
        $emptyObject = new EntityHydrationIdentityUser();

        \Testo\Expect::exception(MapperException::class)->withMessageContaining("Can't hydrate an entity because property and value types are incompatible.");

        try {
            $mapper->hydrate($emptyObject, ['id' => 'string value']);
        } catch (\Throwable $e) {
            \Testo\Assert::instanceOf($e->getPrevious(), \TypeError::class);
            throw $e;
        }
    }

    #[\Testo\Lifecycle\BeforeTest]
    public function setUp(): void
    {
        $this->orm = $this->withSchema(
            new Schema(
                [
                    EntityHydrationUser::class => [
                        SchemaInterface::MAPPER => Mapper::class,
                        SchemaInterface::DATABASE => 'default',
                        SchemaInterface::TABLE => 'user',
                        SchemaInterface::PRIMARY_KEY => 'uuid',
                        SchemaInterface::COLUMNS => ['id', 'username', 'email'],
                        SchemaInterface::TYPECAST => [],
                        SchemaInterface::SCHEMA => [],
                        SchemaInterface::RELATIONS => [],
                    ],
                    ExtendedEntityHydrationUser::class => [
                        SchemaInterface::MAPPER => Mapper::class,
                        SchemaInterface::DATABASE => 'default',
                        SchemaInterface::TABLE => 'user',
                        SchemaInterface::PRIMARY_KEY => 'uuid',
                        SchemaInterface::COLUMNS => ['id', 'username', 'email', 'isVerified', 'profileId'],
                        SchemaInterface::TYPECAST => [],
                        SchemaInterface::SCHEMA => [],
                        SchemaInterface::RELATIONS => [],
                    ],
                    EntityHydrationIdentityUser::class => [
                        SchemaInterface::MAPPER => Mapper::class,
                        SchemaInterface::DATABASE => 'default',
                        SchemaInterface::TABLE => 'user',
                        SchemaInterface::PRIMARY_KEY => 'id',
                        SchemaInterface::COLUMNS => ['id', 'value'],
                        SchemaInterface::TYPECAST => [],
                        SchemaInterface::SCHEMA => [],
                        SchemaInterface::RELATIONS => [],
                    ],
                ],
            ),
        );
    }
}
// phpcs:disable
class EntityHydrationIdentityUser
{
    public int $id;
}

class EntityHydrationUser
{
    public int $id;
    protected string $username;
    private string $email;
    private array $attributes = [];

    public function __construct(int $id, string $username, string $email)
    {
        $this->id = $id;
        $this->username = $username;
        $this->email = $email;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getUsername(): string
    {
        return $this->username;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function getAttributes(): array
    {
        return $this->attributes;
    }

    public function __set(string $name, $value): void
    {
        $this->attributes[$name] = $value;
    }

    public function __get(string $name)
    {
        if (isset($this->attributes[$name])) {
            return $this->attributes[$name];
        }
    }
}


class ExtendedEntityHydrationUser extends EntityHydrationUser
{
    protected bool $isVerified;
    private int $profileId;

    public function __construct(int $id, string $username, string $email, bool $isVerified, int $profileId)
    {
        parent::__construct($id, $username, $email);

        $this->isVerified = $isVerified;
        $this->profileId = $profileId;
    }

    public function isVerified(): bool
    {
        return $this->isVerified;
    }

    public function getProfileId(): int
    {
        return $this->profileId;
    }
}
// phpcs:enable
