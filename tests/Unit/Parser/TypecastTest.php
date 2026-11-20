<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Unit\Parser;

use Cycle\Database\DatabaseInterface;
use Cycle\Database\Driver\DriverInterface;
use Cycle\ORM\Exception\TypecastException;
use Cycle\ORM\Parser\Typecast;
use Cycle\ORM\Tests\Fixtures\Enum\CustomStringable;
use Cycle\ORM\Tests\Fixtures\Enum\TypeIntEnum;
use Cycle\ORM\Tests\Fixtures\Enum\TypeStringEnum;
use Cycle\ORM\Tests\Fixtures\StaticCallableRule;
use Cycle\ORM\Tests\Fixtures\Uuid;
use Mockery as m;
use Testo\Assert;
use Testo\Data\DataProvider;
use Testo\Expect;
use Testo\Lifecycle\BeforeTest;
use Testo\Test;

#[Test]
final class TypecastTest
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

        Assert::same($this->typecast->setRules($rules), ['uuid' => 'uuid']);
    }

    public function testCastIntValue(): void
    {
        $this->typecast->setRules(['id' => 'int']);

        Assert::same($this->typecast->cast(['id' => '10', 'foo' => '5']), [
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
                'digits then letters' => [['foo' => '0x'], ['foo' => null]],
                'letters then digits' => [['foo' => 'x0'], ['foo' => null]],
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
    #[DataProvider('enumCastDataProvider')]
    public function testEnumCast(array $rules, array $in, array $out): void
    {
        $this->typecast->setRules($rules);

        Assert::same($this->typecast->cast($in), $out);
    }

    public function testCastBoolValue(): void
    {
        $this->typecast->setRules(['is_admin' => 'bool']);

        Assert::same($this->typecast->cast(['is_admin' => 0, 'foo' => 1]), [
            'is_admin' => false,
            'foo' => 1,
        ]);
    }

    public function testCastFloatValue(): void
    {
        $this->typecast->setRules(['price' => 'float']);

        Assert::same($this->typecast->cast(['price' => 100, 'foo' => '5']), [
            'price' => 100.0,
            'foo' => '5',
        ]);
    }

    public function testCastDateTimeValue(): void
    {
        $this->db->shouldReceive('getDriver')->once()->andReturn($driver = m::mock(DriverInterface::class));
        $driver->shouldReceive('getTimezone')->once()->andReturn(new \DateTimeZone('Europe/Berlin'));

        $this->typecast->setRules(['date' => 'datetime']);

        $data = $this->typecast->cast(['date' => '2010-05-10 12:04:10', 'foo' => '2010-05-10']);

        Assert::same($data['date']->format(\DateTimeInterface::ATOM), '2010-05-10T12:04:10+02:00');
        Assert::same($data['foo'], '2010-05-10');
    }

    public function testCastValueWithNonExistType(): void
    {
        $this->typecast->setRules(['foo' => 'bar']);

        Assert::same($this->typecast->cast(['price' => '10000']), [
            'price' => '10000',
        ]);
    }

    public function testCastCallableValue(): void
    {
        $this->typecast->setRules(['uuid' => [Uuid::class, 'parse']]);

        $uuid = \Ramsey\Uuid\Uuid::fromString('71ceb213-ec3d-4ae5-911b-ba042abfb204');
        $result = $this->typecast->cast(['uuid' => $uuid->getBytes()]);

        Assert::instanceOf($result['uuid'], Uuid::class);
        Assert::same($result['uuid']->toString(), '71ceb213-ec3d-4ae5-911b-ba042abfb204');
    }

    #[DataProvider('callablesWithArgs')]
    public function testCastCallableWithArguments(array $callable, array $args, bool $hasDatabase): void
    {
        $this->typecast->setRules(['callable' => [...$callable, $args]]);

        $result = $this->typecast->cast(['callable' => 'baz'])['callable'];

        Assert::same($result['value'], 'baz', 'Value should be "baz"');
        $hasDatabase and Assert::instanceOf($result['database'], DatabaseInterface::class, 'Database passed');

        $isVariadic = (new \ReflectionFunction(\Closure::fromCallable($callable)))->isVariadic();
        Assert::same($result['arguments'], $isVariadic ? $args : \array_values($args), 'Arguments must be the same as passed');
    }

    public function testCastJsonArrayValue(): void
    {
        $this->typecast->setRules(['foo' => 'json', 'baz' => 'json']);

        $data = $this->typecast->cast([
            'foo' => \json_encode(['foo' => 'bar']),
            'bar' => \json_encode(['bar' => 'baz']),
            'baz' => null,
        ]);

        Assert::same($data['foo'], ['foo' => 'bar']);
        Assert::same($data['bar'], \json_encode(['bar' => 'baz']));
        Assert::null($data['baz']);
    }

    public function testCastJsonScalarValue(): void
    {
        $this->typecast->setRules(['foo' => 'json', 'bar' => 'json', 'baz' => 'json']);

        $data = $this->typecast->cast([
            'foo' => \json_encode(1917, \JSON_THROW_ON_ERROR),
            'bar' => \json_encode('string', \JSON_THROW_ON_ERROR),
            'baz' => \json_encode(3.14, \JSON_THROW_ON_ERROR),
        ]);

        Assert::same($data['foo'], 1917);
        Assert::same($data['bar'], 'string');
        Assert::same($data['baz'], 3.14);

        $val = $this->typecast->uncast($data);
        Assert::same($val['foo'], "1917");
        Assert::same($val['bar'], '"string"');
        Assert::same($val['baz'], "3.14");
    }

    public function castsNumericStringToFloat(): void
    {
        $this->typecast->setRules(['price' => 'float']);

        Assert::same($this->typecast->cast(['price' => '9.99'])['price'], 9.99);
    }

    public function decodesJsonUpToDefaultDepth(): void
    {
        $this->typecast->setRules(['foo' => 'json']);

        $data = $this->typecast->cast(['foo' => \str_repeat('[', 511) . \str_repeat(']', 511)]);

        Assert::count($data['foo'], 1);
    }

    public function rejectsJsonDeeperThanDefaultDepth(): never
    {
        $this->typecast->setRules(['foo' => 'json']);

        Expect::exception(TypecastException::class);

        $this->typecast->cast(['foo' => \str_repeat('[', 512) . \str_repeat(']', 512)]);
    }

    public function rejectsInvalidJson(): never
    {
        $this->typecast->setRules(['foo' => 'json']);

        Expect::exception(TypecastException::class);

        $this->typecast->cast(['foo' => '{invalid']);
    }

    public function substitutesInvalidUtf8InJson(): void
    {
        $this->typecast->setRules(['foo' => 'json']);

        $data = $this->typecast->cast(['foo' => "\"a\xB1\""]);
        $raw = $this->typecast->uncast(['foo' => "a\xB1"]);

        Assert::same($data['foo'], "a\u{FFFD}");
        Assert::same($raw['foo'], "\"a\u{FFFD}\"");
    }

    public function uncastKeepsUnicodeUnescaped(): void
    {
        $this->typecast->setRules(['foo' => 'json']);

        Assert::same($this->typecast->uncast(['foo' => ['é']])['foo'], '["é"]');
    }

    public function leavesUnknownRulesUntouched(): void
    {
        $rules = [
            'uuid' => 'uuid',
            'tail' => [StaticCallableRule::class, 'invoke', [], 'extra'],
            'id' => 'int',
        ];

        Assert::same($this->typecast->setRules($rules), [
            'uuid' => 'uuid',
            'tail' => [StaticCallableRule::class, 'invoke', [], 'extra'],
        ]);
    }

    public function rejectsCallableArgumentsThatAreNotArray(): never
    {
        Expect::exception(\InvalidArgumentException::class)
            ->withMessage('The third argument of typecast rule for the `role.foo` must be an array of arguments.');

        $this->typecast->setRules(['foo' => [StaticCallableRule::class, 'invoke', 'bar']]);
    }

    public function castsWithSingleParameterCallableAndNoArguments(): void
    {
        @$this->typecast->setRules(['uuid' => [\Ramsey\Uuid\Uuid::class, 'fromString', []]]);

        $result = $this->typecast->cast(['uuid' => '71ceb213-ec3d-4ae5-911b-ba042abfb204']);

        Assert::same($result['uuid']->toString(), '71ceb213-ec3d-4ae5-911b-ba042abfb204');
    }

    public function testUncast(): void
    {
        $this->typecast->setRules(['foo' => 'json', 'baz' => 'json']);

        $data = $this->typecast->uncast([
            'foo' => ['foo' => 'bar'],
            'bar' => ['bar' => 'baz'],
            'baz' => null,
        ]);

        Assert::same($data['foo'], json_encode(['foo' => 'bar']));
        Assert::same($data['bar'], ['bar' => 'baz']);
        Assert::null($data['baz']);
    }

    #[BeforeTest]
    protected function setUp(): void
    {
        $this->typecast = new Typecast(
            'role',
            $this->db = m::mock(DatabaseInterface::class),
        );
    }
}
