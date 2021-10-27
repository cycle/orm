<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Unit\Heap;

use Cycle\ORM\Heap\Node;
use PHPUnit\Framework\TestCase;

class NodeComparisonTest extends TestCase
{
    public function valuesProvider(): array
    {
        // todo add comparision with object
        return [
            // same:
            [true, true, true],
            [true, 1, '1'],
            [true, -1, '-1'],
            [true, 2, '2'],
            [true, 1, true],
            [true, '1', true],
            [true, '0', false],
            [true, 0, false],
            [true, 0, '0'],
            [true, 10, '10'],
            [true, null, null],
            [true, '', b''],
            [true, 2.1, '2.1'],
            [true, 3, 3.0],
            [true, 199, 199.0],
            [true, '199.0', 199.0],
            // not same:
            [false, 0, "\0"],
            [false, null, "\0"],
            [false, null, 0],
            [false, null, -1],
            [false, null, 1],
            [false, null, ''],
            [false, null, false],
            [false, null, true],
            [false, [], null],
            [false, [], true],
            [false, [], false],
            [false, [], ''],
            [false, true, false],
            [false, 2, '1'],
            [false, -1, true],
            [false, -1, false],
            [false, '-1', true],
            [false, '-2', true],
            [false, '', false],
            [false, '', null],
            [false, 2, true],
            [false, 2, false],
            [false, 0, true],
            [false, 300.0, 400],
        ];
    }

    /**
     * @dataProvider valuesProvider
     */
    public function testCompare(bool $same, mixed $a, mixed $b): void
    {
        $values = sprintf(
            '$a = (%s) %s and $b = (%s) %s',
            \get_debug_type($a),
            \var_export($a, true),
            \get_debug_type($b),
            \var_export($b, true)
        );
        if ($same) {
            $this->assertSame(0, Node::compare($a, $b), 'Should be same: ' . $values);
        } else {
            $this->assertNotSame(0, Node::compare($a, $b), 'Shouldn\'t be same: ' . $values);
        }
    }
}
