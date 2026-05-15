<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Functional\Driver\Postgres\Inheritance\STI;

use Cycle\ORM\Select;
use Cycle\ORM\Tests\Functional\Driver\Common\Inheritance\Fixture\Employee;
use Cycle\ORM\Tests\Functional\Driver\Common\Inheritance\Fixture\Manager;
use Cycle\ORM\Tests\Functional\Driver\Common\Inheritance\STI\SimpleTest;

/**
 * @group driver
 * @group driver-postgres
 */
class CursorTest extends SimpleTest
{
    public const DRIVER = 'postgres';

    public function testCursorReturnsCorrectSubclassInstances(): void
    {
        $entities = $this->getDatabase()->transaction(function (): array {
            $out = [];
            foreach ((new Select($this->orm, Employee::class))->cursor(2) as $entity) {
                $out[] = $entity;
            }
            return $out;
        });

        $this->assertCount(4, $entities);

        $this->assertInstanceOf(Manager::class, $entities[0]);

        $this->assertInstanceOf(Employee::class, $entities[1]);
        $this->assertNotInstanceOf(Manager::class, $entities[1]);

        $this->assertInstanceOf(Manager::class, $entities[2]);

        $this->assertInstanceOf(Employee::class, $entities[3]);
        $this->assertNotInstanceOf(Manager::class, $entities[3]);

        $this->assertSame([1, 2, 3, 4], \array_map(fn($e) => $e->id, $entities));
    }
}
