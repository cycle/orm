<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Unit\Parser;

use Cycle\Database\DatabaseInterface;
use Cycle\Database\Driver\DriverInterface;
use Cycle\ORM\Parser\Typecast;
use Cycle\ORM\Tests\Fixtures\Enum\CustomStringable;
use Cycle\ORM\Tests\Fixtures\Enum\TypeIntEnum;
use Cycle\ORM\Tests\Fixtures\Enum\TypeStringEnum;
use Cycle\ORM\Tests\Fixtures\Uuid;
use Mockery as m;
use PHPUnit\Framework\TestCase;
use ReflectionEnum;

class TypecastTest extends TestCase
{
    private Typecast $typecast;
    private m\LegacyMockInterface|m\MockInterface|DatabaseInterface $db;

    protected function setUp(): void
    {
        parent::setUp();

        $this->typecast = new Typecast(
            $this->db = m::mock(DatabaseInterface::class)
        );
    }

    public function testSetRules(): void
    {
        $rules = [
            'id' => 'int',
            'guest' => 'bool',
            'bonus' => 'float',
            'date' => 'datetime',
            'slug' => fn (string $value) => strtolower($value),
            'title' => 'strtoupper',
            'test' => [Uuid::class, 'create'],
            'uuid' => 'uuid',
        ];

        $this->assertSame(['uuid' => 'uuid'], $this->typecast->setRules($rules));
    }

    public function testCastIntValue(): void
    {
        $this->typecast->setRules(['id' => 'int']);

        $this->assertSame([
            'id' => 10,
            'foo' => '5',
        ], $this->typecast->cast(['id' => '10', 'foo' => '5']));
    }

    public function enumCastDataProvider(): iterable
    {
        if (\PHP_VERSION_ID < 80100) {
            return;
        }
        $getCase = static fn (string $enum, string $case) => (new ReflectionEnum($enum))
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
     *
     * @dataProvider enumCastDataProvider
     */
    public function testEnumCast(array $rules, array $in, array $out): void
    {
        $this->typecast->setRules($rules);

        $this->assertSame($out, $this->typecast->cast($in));
    }

    public function testCastBoolValue(): void
    {
        $this->typecast->setRules(['is_admin' => 'bool']);

        $this->assertSame([
            'is_admin' => false,
            'foo' => 1,
        ], $this->typecast->cast(['is_admin' => 0, 'foo' => 1]));
    }

    public function testCastFloatValue(): void
    {
        $this->typecast->setRules(['price' => 'float']);

        $this->assertSame([
            'price' => 100.0,
            'foo' => '5',
        ], $this->typecast->cast(['price' => 100, 'foo' => '5']));
    }

    public function testCasDateTimeValue(): void
    {
        $this->db->shouldReceive('getDriver')->once()->andReturn($driver = m::mock(DriverInterface::class));
        $driver->shouldReceive('getTimezone')->once()->andReturn(new \DateTimeZone('Europe/Berlin'));

        $this->typecast->setRules(['date' => 'datetime']);

        $data = $this->typecast->cast(['date' => '2010-05-10 12:04:10', 'foo' => '2010-05-10']);

        $this->assertSame('2010-05-10T12:04:10+02:00', $data['date']->format(\DateTimeInterface::ATOM));
        $this->assertSame('2010-05-10', $data['foo']);
    }

    public function testCastValueWithNonExistType(): void
    {
        $this->typecast->setRules(['foo' => 'bar']);

        $this->assertSame([
            'price' => '10000',
        ], $this->typecast->cast(['price' => '10000']));
    }

    public function testCastCallableValue(): void
    {
        $this->typecast->setRules(['uuid' => [Uuid::class, 'parse']]);

        $uuid = \Ramsey\Uuid\Uuid::fromString('71ceb213-ec3d-4ae5-911b-ba042abfb204');
        $result = $this->typecast->cast(['uuid' => $uuid->getBytes()]);

        $this->assertInstanceOf(Uuid::class, $result['uuid']);
        $this->assertSame('71ceb213-ec3d-4ae5-911b-ba042abfb204', $result['uuid']->toString());
    }
}
