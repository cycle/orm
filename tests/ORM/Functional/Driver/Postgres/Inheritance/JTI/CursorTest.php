<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Functional\Driver\Postgres\Inheritance\JTI;

use Cycle\ORM\Select;
use Cycle\ORM\Tests\Functional\Driver\Common\Inheritance\Fixture\Employee;
use Cycle\ORM\Tests\Functional\Driver\Common\Inheritance\Fixture\Engineer;
use Cycle\ORM\Tests\Functional\Driver\Common\Inheritance\Fixture\Manager;
use Cycle\ORM\Tests\Functional\Driver\Common\Inheritance\Fixture\Programator;
use Cycle\ORM\Tests\Functional\Driver\Common\Inheritance\JTI\SimpleCasesTest;

/**
 * @group driver
 * @group driver-postgres
 */
class CursorTest extends SimpleCasesTest
{
    public const DRIVER = 'postgres';

    public function testCursorReturnsCorrectHierarchyInstances(): void
    {
        $entities = $this->getDatabase()->transaction(function (): array {
            $out = [];
            $cursor = (new Select($this->orm, static::EMPLOYEE_ROLE))
                ->orderBy('id')
                ->cursor(2);
            foreach ($cursor as $entity) {
                $out[] = $entity;
            }
            return $out;
        });

        $this->assertCount(4, $entities);

        // id=1 → Manager (rank='top')
        $this->assertInstanceOf(Manager::class, $entities[0]);
        $this->assertSame('top', $entities[0]->rank);

        // id=2 → Programator (engineer.level=8, programator.language='php')
        $this->assertInstanceOf(Programator::class, $entities[1]);
        $this->assertSame(8, $entities[1]->level);
        $this->assertSame('php', $entities[1]->language);

        // id=3 → Manager (rank='bottom')
        $this->assertInstanceOf(Manager::class, $entities[2]);
        $this->assertSame('bottom', $entities[2]->rank);

        // id=4 → Programator (engineer.level=10, programator.language='go')
        $this->assertInstanceOf(Programator::class, $entities[3]);
        $this->assertSame(10, $entities[3]->level);
        $this->assertSame('go', $entities[3]->language);
    }

    public function testCursorOnChildRoleReturnsFullParentColumns(): void
    {
        $entities = $this->getDatabase()->transaction(function (): array {
            $out = [];
            // Walk just the programator role — should include parent columns from employee + engineer.
            $cursor = (new Select($this->orm, static::PROGRAMATOR_ROLE))
                ->orderBy('id')
                ->cursor(10);
            foreach ($cursor as $entity) {
                $out[] = $entity;
            }
            return $out;
        });

        $this->assertCount(2, $entities);
        foreach ($entities as $programator) {
            $this->assertInstanceOf(Programator::class, $programator);
            // Parent columns are populated
            $this->assertNotEmpty($programator->name);
            $this->assertNotNull($programator->age);
            $this->assertGreaterThan(0, $programator->level);
            $this->assertNotEmpty($programator->language);
        }
    }
}
