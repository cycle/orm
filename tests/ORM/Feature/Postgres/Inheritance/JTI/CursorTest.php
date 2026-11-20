<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Feature\Postgres\Inheritance\JTI;

use Cycle\ORM\Select;
use Cycle\ORM\Tests\Feature\Common\Inheritance\Fixture\Employee;
use Cycle\ORM\Tests\Feature\Common\Inheritance\Fixture\Engineer;
use Cycle\ORM\Tests\Feature\Common\Inheritance\Fixture\Manager;
use Cycle\ORM\Tests\Feature\Common\Inheritance\Fixture\Programator;
use Cycle\ORM\Tests\Feature\Common\Inheritance\JTI\SimpleCasesTest;

class CursorTest extends SimpleCasesTest
{
    #[\Testo\Test]
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

        \Testo\Assert::count($entities, 4);

        // id=1 → Manager (rank='top')
        \Testo\Assert::instanceOf($entities[0], Manager::class);
        \Testo\Assert::same($entities[0]->rank, 'top');

        // id=2 → Programator (engineer.level=8, programator.language='php')
        \Testo\Assert::instanceOf($entities[1], Programator::class);
        \Testo\Assert::same($entities[1]->level, 8);
        \Testo\Assert::same($entities[1]->language, 'php');

        // id=3 → Manager (rank='bottom')
        \Testo\Assert::instanceOf($entities[2], Manager::class);
        \Testo\Assert::same($entities[2]->rank, 'bottom');

        // id=4 → Programator (engineer.level=10, programator.language='go')
        \Testo\Assert::instanceOf($entities[3], Programator::class);
        \Testo\Assert::same($entities[3]->level, 10);
        \Testo\Assert::same($entities[3]->language, 'go');
    }

    #[\Testo\Test]
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

        \Testo\Assert::count($entities, 2);
        foreach ($entities as $programator) {
            \Testo\Assert::instanceOf($programator, Programator::class);
            // Parent columns are populated
            \Testo\Assert::false(empty($programator->name));
            \Testo\Assert::notNull($programator->age);
            \Testo\Assert::numeric($programator->level)->greaterThan(0);
            \Testo\Assert::false(empty($programator->language));
        }
    }
}
