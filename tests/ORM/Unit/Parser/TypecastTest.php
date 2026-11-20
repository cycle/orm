<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Unit\Parser;

use Cycle\Database\DatabaseInterface;
use Cycle\Database\Driver\DriverInterface;
use Cycle\ORM\Parser\Typecast;
use Cycle\ORM\Tests\Fixtures\Enum\CustomStringable;
use Cycle\ORM\Tests\Fixtures\Enum\TypeIntEnum;
use Cycle\ORM\Tests\Fixtures\Enum\TypeStringEnum;
use Cycle\ORM\Tests\Fixtures\StaticCallableRule;
use Cycle\ORM\Tests\Fixtures\Uuid;
use Mockery as m;
use PHPUnit\Framework\TestCase;

class TypecastTest
{
    private Typecast $typecast;
    private m\LegacyMockInterface|m\MockInterface|DatabaseInterface $db;

    public static function callablesWithArgs(): iterable
    {
        yield [[StaticCallableRule::class, 'invoke'], ['bar'], true];
        yield [[StaticCallableRule::class, 'invoke'], [['bar']], true];
        yield [[StaticCallableRule::class, 'invoke'], ['argument' => ['bar']], true];
        yield [[StaticCallableRule::class, 'invokeVariadic'], ['foo' => 'bar'], true];
        yield [[StaticCallableRule::class, 'invokeWithoutDatabaseVariadic'], ['foo' => 'bar'], false];
        yield [[StaticCallableRule::class, 'invokeWithoutDatabaseVariadic'], [1, 2, 42], false];
        yield [[StaticCallableRule::class, 'invokeWithoutDatabase'], [69], false];
    }

    #[\Testo\Test]
    public function testSetRules(): void
    {
        $rules = [
            'id' => 'int',
            'guest' => 'bool',
            'bonus' => 'float',
            'date' => 'datetime',
            'slug' => fn(string $value) => strtolower($value),
            'title' => 'strtoupper',
            'test' => [Uuid::class, 'create'],
            'callable' => [StaticCallableRule::class, 'invoke', ['foo' => 'bar']],
            'uuid' => 'uuid',
            'settings' => 'json',
        ];

        \Testo\Assert::same($this->typecast->setRules($rules), ['uuid' => 'uuid']);
    }

    #[\Testo\Test]
    public function testCastIntValue(): void
    {
        $this->typecast->setRules(['id' => 'int']);

        \Testo\Assert::same($this->typecast->cast(['id' => '10', 'foo' => '5']), [
            'id' => 10,
            'foo' => '5',
        ]);
    }

    public function enumCastDataProvider(): iterable
    {
        if (\PHP_VERSION_ID < 80100) {
            return;
        }
        $getCase = static fn(string $enum, string $case) => (new \ReflectionEnum($enum))
            ->getCase($case)
            ->getValue();

        // String Enum
        foreach (
            [
                'null' => [['foo' => null], ['foo' => null]],
                'guest str' => [['foo' => 'guest'], ['foo' => $getCase(TypeStringEnum::class, 'Guest')]],
                'int' => [['foo' => 0], ['foo' => null]],
                'object' => [['foo' => new \stdClass()], ['foo' => null]],
                'invalid case' => [['foo' => 'foo-bar-baz'], ['foo' => null]],
                'no needed key' => [['bar' => 'guest'], ['bar' => 'guest']],
                'stringable' => [['foo' => new CustomStringable('admin')], ['foo' => null]],
            ] as $k => $v
        ) {
            yield 'string: ' . $k => \array_merge([['foo' => TypeStringEnum::class]], $v);
        }
        // Int Enum
        foreach (
            [
                'null' => [['foo' => null], ['foo' => null]],
                'guest int' => [['foo' => 0], ['foo' => $getCase(TypeIntEnum::class, 'Guest')]],
                'stringed int' => [['foo' => '0'], ['foo' => $getCase(TypeIntEnum::class, 'Guest')]],
                'object' => [['foo' => new \stdClass()], ['foo' => null]],
                'invalid str case' => [['foo' => 'foo-bar-baz'], ['foo' => null]],
                'invalid int case' => [['foo' => -1], ['foo' => null]],
                'no needed key' => [['bar' => 0], ['bar' => 0]],
                'stringable' => [['foo' => new CustomStringable('2')], ['foo' => null]],
            ] as $k => $v
        ) {
            yield 'int: ' . $k => \array_merge([['foo' => TypeIntEnum::class]], $v);
        }
    }

    /**
     * @requires PHP >= 8.1
     */
    #[\Testo\Test]
    #[\Testo\Data\DataProvider('enumCastDataProvider')]
    public function testEnumCast(array $rules, array $in, array $out): void
    {
        $this->typecast->setRules($rules);

        \Testo\Assert::same($this->typecast->cast($in), $out);
    }

    #[\Testo\Test]
    public function testCastBoolValue(): void
    {
        $this->typecast->setRules(['is_admin' => 'bool']);

        \Testo\Assert::same($this->typecast->cast(['is_admin' => 0, 'foo' => 1]), [
            'is_admin' => false,
            'foo' => 1,
        ]);
    }

    #[\Testo\Test]
    public function testCastFloatValue(): void
    {
        $this->typecast->setRules(['price' => 'float']);

        \Testo\Assert::same($this->typecast->cast(['price' => 100, 'foo' => '5']), [
            'price' => 100.0,
            'foo' => '5',
        ]);
    }

    #[\Testo\Test]
    public function testCastDateTimeValue(): void
    {
        $this->db->shouldReceive('getDriver')->once()->andReturn($driver = m::mock(DriverInterface::class));
        $driver->shouldReceive('getTimezone')->once()->andReturn(new \DateTimeZone('Europe/Berlin'));

        $this->typecast->setRules(['date' => 'datetime']);

        $data = $this->typecast->cast(['date' => '2010-05-10 12:04:10', 'foo' => '2010-05-10']);

        \Testo\Assert::same($data['date']->format(\DateTimeInterface::ATOM), '2010-05-10T12:04:10+02:00');
        \Testo\Assert::same($data['foo'], '2010-05-10');
    }

    #[\Testo\Test]
    public function testCastValueWithNonExistType(): void
    {
        $this->typecast->setRules(['foo' => 'bar']);

        \Testo\Assert::same($this->typecast->cast(['price' => '10000']), [
            'price' => '10000',
        ]);
    }

    #[\Testo\Test]
    public function testCastCallableValue(): void
    {
        $this->typecast->setRules(['uuid' => [Uuid::class, 'parse']]);

        $uuid = \Ramsey\Uuid\Uuid::fromString('71ceb213-ec3d-4ae5-911b-ba042abfb204');
        $result = $this->typecast->cast(['uuid' => $uuid->getBytes()]);

        \Testo\Assert::instanceOf($result['uuid'], Uuid::class);
        \Testo\Assert::same($result['uuid']->toString(), '71ceb213-ec3d-4ae5-911b-ba042abfb204');
    }

    #[\Testo\Test]
    #[\Testo\Data\DataProvider('callablesWithArgs')]
    public function testCastCallableWithArguments(array $callable, array $args, bool $hasDatabase): void
    {
        $this->typecast->setRules(['callable' => [...$callable, $args]]);

        $result = $this->typecast->cast(['callable' => 'baz'])['callable'];

        \Testo\Assert::same($result['value'], 'baz', 'Value should be "baz"');
        $hasDatabase and \Testo\Assert::instanceOf($result['database'], DatabaseInterface::class, 'Database passed');

        $isVariadic = (new \ReflectionFunction(\Closure::fromCallable($callable)))->isVariadic();
        \Testo\Assert::same($result['arguments'], $isVariadic ? $args : \array_values($args), 'Arguments must be the same as passed');
    }

    #[\Testo\Test]
    public function testCastJsonArrayValue(): void
    {
        $this->typecast->setRules(['foo' => 'json', 'baz' => 'json']);

        $data = $this->typecast->cast([
            'foo' => \json_encode(['foo' => 'bar']),
            'bar' => \json_encode(['bar' => 'baz']),
            'baz' => null,
        ]);

        \Testo\Assert::same($data['foo'], ['foo' => 'bar']);
        \Testo\Assert::same($data['bar'], \json_encode(['bar' => 'baz']));
        \Testo\Assert::null($data['baz']);
    }

    #[\Testo\Test]
    public function testCastJsonScalarValue(): void
    {
        $this->typecast->setRules(['foo' => 'json', 'bar' => 'json', 'baz' => 'json']);

        $data = $this->typecast->cast([
            'foo' => \json_encode(1917, \JSON_THROW_ON_ERROR),
            'bar' => \json_encode('string', \JSON_THROW_ON_ERROR),
            'baz' => \json_encode(3.14, \JSON_THROW_ON_ERROR),
        ]);

        \Testo\Assert::same($data['foo'], 1917);
        \Testo\Assert::same($data['bar'], 'string');
        \Testo\Assert::same($data['baz'], 3.14);

        $val = $this->typecast->uncast($data);
        \Testo\Assert::same($val['foo'], "1917");
        \Testo\Assert::same($val['bar'], '"string"');
        \Testo\Assert::same($val['baz'], "3.14");
    }

    #[\Testo\Test]
    public function testUncast(): void
    {
        $this->typecast->setRules(['foo' => 'json', 'baz' => 'json']);

        $data = $this->typecast->uncast([
            'foo' => ['foo' => 'bar'],
            'bar' => ['bar' => 'baz'],
            'baz' => null,
        ]);

        \Testo\Assert::same($data['foo'], json_encode(['foo' => 'bar']));
        \Testo\Assert::same($data['bar'], ['bar' => 'baz']);
        \Testo\Assert::null($data['baz']);
    }

    #[\Testo\Lifecycle\BeforeTest]
    protected function setUp(): void
    {
        $this->typecast = new Typecast(
            'role',
            $this->db = m::mock(DatabaseInterface::class),
        );
    }
}
