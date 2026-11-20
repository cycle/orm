<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Feature\Common\Typecast;

use Cycle\ORM\Heap\Node;
use Cycle\ORM\Mapper\Mapper;
use Cycle\ORM\Parser\Typecast;
use Cycle\ORM\Schema;
use Cycle\ORM\SchemaInterface;
use Cycle\ORM\Select;
use Cycle\ORM\Tests\Fixtures\Admin;
use Cycle\ORM\Tests\Fixtures\JsonSerializableClass;
use Cycle\ORM\Tests\Fixtures\User;
use Cycle\ORM\Tests\Feature\Common\Typecast\Fixture\JsonTypecast;
use Cycle\Testing\ORM\OrmTestCase;
use Cycle\Testing\Database\Assert\QueryAssert;

class JsonTest extends OrmTestCase
{

    #[\Testo\Test]
    public function testFetchAll(): void
    {
        $selector = new Select($this->orm, User::class);
        $result = $selector->fetchAll();

        \Testo\Assert::instanceOf($result[0], User::class);
        \Testo\Assert::same($result[0]->id, 1);
        \Testo\Assert::same($result[0]->email, 'hello@world.com');
        \Testo\Assert::same($result[0]->settings, ['theme' => 'dark']);
        \Testo\Assert::null($result[0]->settingsNullable);
        \Testo\Assert::null($result[0]->jsonSerializable);

        \Testo\Assert::instanceOf($result[1], User::class);
        \Testo\Assert::same($result[1]->id, 2);
        \Testo\Assert::same($result[1]->email, 'another@world.com');
        \Testo\Assert::same($result[1]->settings, ['grids' => ['products' => ['columns' => ['id', 'title']]]]);
        \Testo\Assert::same($result[1]->settingsNullable, ['theme' => 'dark']);
        \Testo\Assert::null($result[1]->jsonSerializable);
    }

    #[\Testo\Test]
    public function testNoWrite(): void
    {
        $selector = new Select($this->orm, User::class);
        $result = $selector->fetchOne();
        QueryAssert::writes(0, fn() => $this->save($result));
    }

    #[\Testo\Test]
    public function testStore(): void
    {
        $e = new User();
        $e->email = 'test@email.com';
        $e->settings = ['theme' => 'light'];
        $e->settingsNullable = ['some' => 'data'];
        QueryAssert::writes(1, fn() => $this->save($e));
        QueryAssert::writes(0, fn() => $this->save($e));

        \Testo\Assert::true($this->orm->getHeap()->has($e));
        \Testo\Assert::same($this->orm->getHeap()->get($e)->getStatus(), Node::MANAGED);

        $this->orm->getHeap()->clean();

        $selector = new Select($this->orm, User::class);
        $result = $selector->where('id', $e->id)->fetchOne();
        \Testo\Assert::same($result->email, 'test@email.com');
        \Testo\Assert::same($result->settings, ['theme' => 'light']);
        \Testo\Assert::same($result->settingsNullable, ['some' => 'data']);
    }

    /**
     * @throws \Throwable
     */
    #[\Testo\Test]
    public function testStoresEmptyArraySettings(): void
    {
        $e = new User();
        $e->email = 'test@email.com';
        $e->settings = [];
        $e->settingsNullable = [];
        QueryAssert::writes(1, fn() => $this->save($e));
        QueryAssert::writes(0, fn() => $this->save($e));

        \Testo\Assert::true($this->orm->getHeap()->has($e));
        \Testo\Assert::same($this->orm->getHeap()->get($e)->getStatus(), Node::MANAGED);

        $this->orm->getHeap()->clean();

        $selector = new Select($this->orm, User::class);
        $result = $selector->where('id', $e->id)->fetchOne();
        \Testo\Assert::same($result->email, 'test@email.com');
        \Testo\Assert::same($result->settings, []);
        \Testo\Assert::same($result->settingsNullable, []);
    }

    #[\Testo\Test]
    public function testStoresNullSettings(): void
    {
        $e = new User();
        $e->email = 'test@email.com';
        $e->settings = [];
        $e->settingsNullable = null;
        QueryAssert::writes(1, fn() => $this->save($e));
        QueryAssert::writes(0, fn() => $this->save($e));

        \Testo\Assert::true($this->orm->getHeap()->has($e));
        \Testo\Assert::same($this->orm->getHeap()->get($e)->getStatus(), Node::MANAGED);

        $this->orm->getHeap()->clean();

        $selector = (new Select($this->orm, User::class))->where('id', $e->id);
        $result = $selector->fetchOne();
        \Testo\Assert::same($result->email, 'test@email.com');
        \Testo\Assert::same($result->settings, []);
        \Testo\Assert::same($result->settingsNullable, null);

        $result = $selector->fetchData(false)[0];
        \Testo\Assert::null($result['settingsNullable']);
    }

    #[\Testo\Test]
    public function testStoreJsonSerializable(): void
    {
        $e = new User();
        $e->email = 'test@email.com';
        $e->settings = ['theme' => 'light'];
        $e->jsonSerializable = new JsonSerializableClass();
        QueryAssert::writes(1, fn() => $this->save($e));
        QueryAssert::writes(0, fn() => $this->save($e));

        \Testo\Assert::true($this->orm->getHeap()->has($e));
        \Testo\Assert::same($this->orm->getHeap()->get($e)->getStatus(), Node::MANAGED);

        $this->orm->getHeap()->clean();

        $result = $this->getDatabase()->table('users')->select()->where('id', $e->id)->fetchAll();
        \Testo\Assert::equals(\json_decode($result[0]['json_serializable'], true), (new JsonSerializableClass())->jsonSerialize());
    }

    #[\Testo\Test]
    public function testUpdate(): void
    {
        $e = $this->orm->get('user', ['id' => 1]);
        $e->settings = ['theme' => 'light'];
        QueryAssert::writes(1, fn() => $this->save($e));
        QueryAssert::writes(0, fn() => $this->save($e));

        $this->orm->getHeap()->clean();

        $selector = new Select($this->orm, User::class);
        $result = $selector->where('id', 1)->fetchOne();
        \Testo\Assert::same($result->settings, ['theme' => 'light']);
    }

    #[\Testo\Test]
    public function testOverrideTypecast(): void
    {
        $selector = new Select($this->orm, Admin::class);
        $result = $selector->fetchAll();

        \Testo\Assert::same($result[0]->settings, ['json']);

        $e = new Admin();
        $e->email = 'test@email.com';
        $e->settings = ['theme' => 'light'];
        QueryAssert::writes(1, fn() => $this->save($e));

        $this->orm->getHeap()->clean();

        $result = $this->getDatabase()->table('users')->select()->where('id', $e->id)->fetchAll();
        \Testo\Assert::same($result[0]['settings'], 'uncast-json');
    }

    #[\Testo\Lifecycle\BeforeTest]
    public function setUp(): void
    {
        $this->makeTable(table: 'users', columns: [
            'id' => 'primary',
            'email' => 'string',
            'settings' => 'text',
            'settings_nullable' => 'text,nullable',
            'json_serializable' => 'text,nullable',
        ], defaults: ['settings' => null, 'settings_nullable' => null, 'json_serializable' => null]);

        $this->getDatabase()->table('users')->insertOne(
            [
                'email' => 'hello@world.com',
                'settings' => \json_encode(['theme' => 'dark']),
                'settings_nullable' => null,
                'json_serializable' => null,
            ],
        );
        $this->getDatabase()->table('users')->insertOne(
            [
                'email' => 'another@world.com',
                'settings' => \json_encode(['grids' => ['products' => ['columns' => ['id', 'title']]]]),
                'settings_nullable' => \json_encode(['theme' => 'dark']),
                'json_serializable' => null,
            ],
        );

        $mapping = [
            SchemaInterface::ROLE => 'user',
            SchemaInterface::MAPPER => Mapper::class,
            SchemaInterface::DATABASE => 'default',
            SchemaInterface::TABLE => 'users',
            SchemaInterface::PRIMARY_KEY => 'id',
            SchemaInterface::COLUMNS => [
                'id' => 'id',
                'email' => 'email',
                'settings' => 'settings',
                'settingsNullable' => 'settings_nullable',
                'jsonSerializable' => 'json_serializable',
            ],
            SchemaInterface::TYPECAST => [
                'id' => 'int',
                'settings' => 'json',
                'settingsNullable' => 'json',
                'jsonSerializable' => 'json',
            ],
            SchemaInterface::SCHEMA => [],
            SchemaInterface::RELATIONS => [],
        ];

        $this->orm = $this->withSchema(new Schema([
            User::class => $mapping,
            Admin::class => [
                SchemaInterface::ROLE => 'admin',
                SchemaInterface::TYPECAST_HANDLER => [
                    JsonTypecast::class,
                    Typecast::class,
                ],
            ] + $mapping,
        ]));
    }
}
