<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Unit\Parser;

use Cycle\Database\DatabaseInterface;
use Cycle\ORM\Parser\Typecast;
use Cycle\ORM\Tests\Fixtures\Uuid;
use PHPUnit\Framework\TestCase;

class TypecastTest extends TestCase
{
    private Typecast $typecast;

    protected function setUp(): void
    {
        parent::setUp();

        $this->typecast = new Typecast(
            $this->createMock(DatabaseInterface::class)
        );
    }

    public function testApplyRules()
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
}
