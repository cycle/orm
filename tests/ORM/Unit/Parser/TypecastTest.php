<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Unit\Parser;

use Cycle\Database\DatabaseInterface;
use Cycle\Database\Driver\DriverInterface;
use Cycle\ORM\Parser\Typecast;
use Cycle\ORM\Tests\Fixtures\Uuid;
use Mockery as m;
use PHPUnit\Framework\TestCase;

class TypecastTest extends TestCase
{
    private Typecast $typecast;
    private \Mockery\LegacyMockInterface|\Mockery\MockInterface|DatabaseInterface $db;

    protected function setUp(): void
    {
        parent::setUp();

        $this->typecast = new Typecast(
            $this->db = m::mock(DatabaseInterface::class)
        );
    }

    public function testSetRules()
    {
        $rules = [
            'id' => 'int',
            'guest' => 'bool',
            'bonus' => 'float',
            'date' => 'datetime',
            'slug' => fn(string $value) => strtolower($value),
            'title' => 'strtoupper',
            'test' => [Uuid::class, 'create'],
            'uuid' => 'uuid',
        ];

        $this->assertSame(['uuid' => 'uuid'], $this->typecast->setRules($rules));
    }

    public function testCastIntValue()
    {
        $this->typecast->setRules(['id' => 'int']);

        $this->assertSame([
            'id' => 10,
            'foo' => '5'
        ], $this->typecast->cast(['id' => '10', 'foo' => '5']));
    }

    public function testCastBoolValue()
    {
        $this->typecast->setRules(['is_admin' => 'bool']);

        $this->assertSame([
            'is_admin' => false,
            'foo' => 1
        ], $this->typecast->cast(['is_admin' => 0, 'foo' => 1]));
    }

    public function testCastFloatValue()
    {
        $this->typecast->setRules(['price' => 'float']);

        $this->assertSame([
            'price' => 100.0,
            'foo' => '5'
        ], $this->typecast->cast(['price' => 100, 'foo' => '5']));
    }

    public function testCasDateTimeValue()
    {
        $this->db->shouldReceive('getDriver')->once()->andReturn($driver = m::mock(DriverInterface::class));
        $driver->shouldReceive('getTimezone')->once()->andReturn(new \DateTimeZone('Europe/Berlin'));

        $this->typecast->setRules(['date' => 'datetime']);

        $data = $this->typecast->cast(['date' => '2010-05-10 12:04:10', 'foo' => '2010-05-10']);

        $this->assertSame('2010-05-10T12:04:10+02:00', $data['date']->format(\DateTimeInterface::ATOM));
        $this->assertSame('2010-05-10', $data['foo']);
    }

    public function testCastValueWithNonExistType()
    {
        $this->typecast->setRules(['foo' => 'bar']);

        $this->assertSame([
            'price' => '10000',
        ], $this->typecast->cast(['price' => '10000']));
    }

    public function testCastCallableValue()
    {
        $this->typecast->setRules(['uuid' => [Uuid::class, 'parse']]);

        $uuid = \Ramsey\Uuid\Uuid::fromString('71ceb213-ec3d-4ae5-911b-ba042abfb204');
        $result = $this->typecast->cast(['uuid' => $uuid->getBytes()]);

        $this->assertInstanceOf(Uuid::class, $result['uuid']);
        $this->assertSame('71ceb213-ec3d-4ae5-911b-ba042abfb204', $result['uuid']->toString());
    }
}
