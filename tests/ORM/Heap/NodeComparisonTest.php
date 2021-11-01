<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Heap;

use Cycle\ORM\Heap\Node;
use DateTime;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use Stringable;

class NodeComparisonTest extends TestCase
{
    public function equalValuesProvider(): iterable
    {
        yield from [
            // Null
            [null, null],
            // Bool
            [true, true],
            [true, 1],
            [true, '1'],
            [false, '0'],
            [false, 0],
            // Numeric
            [3, 3.0],
            [199, 199.0],
            [1, '1'],
            [-1, '-1'],
            [2, '2'],
            [0, '0'],
            [10, '10'],
            [2.1, '2.1'],
            [199.0, '199.0'],
            // String
            ['', b''],
        ];

        // Datetime
        $t = new DateTime();
        yield 'same datetime object' => [$t, $t];
        yield 'same mutable and immutable' => [$t, DateTimeImmutable::createFromMutable($t)];
        yield 'different objects from same mutable' => [
            DateTimeImmutable::createFromMutable($t),
            DateTimeImmutable::createFromMutable($t),
        ];
        // Custom object
        $obj = $this->createStringableObject('obj');
        yield 'same custom objects' => [$obj, $obj];
        yield 'Stringable object that returns "1" with int 1' => [$this->createStringableObject('1'), 1];

        $obj1 = $this->createStringableObject('obj');
        $obj2 = $this->createStringableObject('o', 'b', 'j');
        \assert($obj1->__toString() === $obj2->__toString());
        \assert((array)$obj1 !== (array)$obj2);
        yield 'different custom objects with same result from __toString()' => [$obj1, $obj2];

        yield 'Stringable and string' => [$obj1, 'obj'];
        yield 'Not Stringable, same class same props' => [
            $this->createNotStringableObject(null, 'foo'),
            $this->createNotStringableObject(null, 'foo'),
        ];
    }

    public function notEqualValuesProvider(): iterable
    {
        yield from [
            // Null
            [null, "\0"],
            [null, 0],
            [null, -1],
            [null, 1],
            [null, ''],
            [null, false],
            [null, true],
            // Numeric
            [0, '00'],
            [0, "\0"],
            [0, '0x0'],
            [2, '1'],
            [300.0, 400],
            // Array
            [[], null],
            [[], true],
            [[], false],
            [[], ''],
            // Bool
            [false, true],
            [false, -1],
            [false, ''],
            [false, 2],
            [true, -1],
            [true, '-1'],
            [true, '-2'],
            [null, ''],
            [true, 2],
            [true, 0],
        ];
        // Datetime
        yield 'different Datetime same second' => [new DateTimeImmutable(), new DateTimeImmutable()];
        // Custom object
        yield 'different objects' => [$this->createStringableObject('foo'), $this->createStringableObject('bar')];
        yield 'Stringable and string' => [$this->createStringableObject('foo'), 'bar'];
        yield 'Not Stringable, same class different props' => [
            $this->createNotStringableObject(null, 'bar'),
            $this->createNotStringableObject(null, 'foo'),
        ];
        yield 'Equals classes with different names' => [
            $this->createNotStringableObject(null, 'foo'),
            $this->createNotStringableObject2(null, 'foo'),
        ];
        yield 'Datetime and not Stringable' => [
            new DateTimeImmutable(),
            $this->createNotStringableObject(null, 'foo'),
        ];
        yield 'not Stringable object and string' => [
            $this->createNotStringableObject(null, 'foo'),
            'bar',
        ];
    }

    /**
     * @dataProvider notEqualValuesProvider
     */
    public function testCompareNotEqual($a, $b): void
    {
        $this->assertNotSame(0, Node::compare($a, $b), 'Shouldn\'t be same: ' . $this->exportVars($a, $b));
    }

    /**
     * @dataProvider equalValuesProvider
     */
    public function testCompareEqual($a, $b): void
    {
        $this->assertSame(0, Node::compare($a, $b), 'Should be same: ' . $this->exportVars($a, $b));
    }

    private function exportVars($a, $b): string
    {
        return sprintf(
            '$a = (%s) %s and $b = (%s) %s',
            \get_debug_type($a),
            \var_export($a, true),
            \get_debug_type($b),
            \var_export($b, true)
        );
    }

    private function createStringableObject(string ...$toConcat): object
    {
        return new class ($toConcat) implements Stringable {
            private $toConcat;

            public function __construct(array $toConcat)
            {
                $this->toConcat = $toConcat;
            }

            public function __toString(): string
            {
                return \implode('', $this->toConcat);
            }
        };
    }

    private function createNotStringableObject($a = null, $b = null): object
    {
        return new class ($a, $b) {
            private $a;
            private $b;

            public function __construct($a, $b)
            {
                $this->a = $a;
                $this->b = $b;
            }
        };
    }

    private function createNotStringableObject2($a = null, $b = null): object
    {
        return new class ($a, $b) {
            private $a;
            private $b;

            public function __construct($a, $b)
            {
                $this->a = $a;
                $this->b = $b;
            }
        };
    }
}
