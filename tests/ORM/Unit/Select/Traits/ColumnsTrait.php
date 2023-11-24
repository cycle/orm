<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Unit\Select\Traits;

use PHPUnit\Framework\TestCase;

final class ColumnsTrait extends TestCase
{
    private const FIELDS = ['id' => 'column_id'];

    public static function getColumnsDataProvider(): iterable
    {
        yield ['id', 'column_id'];
        yield ['name', null];
        yield ['id->foo', 'column_id->foo'];
        yield ['name->foo', null];
        yield ['->foo', null];
        yield ['', null];
    }

    /**
     * @dataProvider getColumnsDataProvider
     */
    public function testGetColumns(string $input, ?string $expected): void
    {
        $class = $this->prepareClass();

        for ($i = 10000; $i > 0; --$i) {
            $class->fieldAlias($input);
        }

        $this->assertSame($expected, $class->fieldAlias($input));
    }

    private function prepareClass(): object
    {
        return new class (self::FIELDS) {
            use \Cycle\ORM\Select\Traits\ColumnsTrait
            {
                fieldAlias as public;
            }

            public function __construct(array $columns)
            {
                $this->columns = $columns;
            }

            public function getAlias(): string
            {
                return 'test';
            }
        };
    }
}
