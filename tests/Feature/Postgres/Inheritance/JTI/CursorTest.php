<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Feature\Postgres\Inheritance\JTI;

use Cycle\ORM\Select;
use Cycle\ORM\Tests\Feature\Common\Inheritance\Fixture\Employee;
use Cycle\ORM\Tests\Feature\Common\Inheritance\Fixture\Engineer;
use Cycle\ORM\Tests\Feature\Common\Inheritance\Fixture\Manager;
use Cycle\ORM\Tests\Feature\Common\Inheritance\Fixture\Programator;
use Cycle\ORM\Tests\Feature\Common\Inheritance\JTI\SimpleCasesTest;
use Testo\Assert;
use Testo\Test;

final class CursorTest extends SimpleCasesTest
{
    #[Test]
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

        Assert::count($entities, 4);

        // id=1 → Manager (rank='top')
        Assert::instanceOf($entities[0], Manager::class);
        Assert::same($entities[0]->rank, 'top');

        // id=2 → Programator (engineer.level=8, programator.language='php')
        Assert::instanceOf($entities[1], Programator::class);
        Assert::same($entities[1]->level, 8);
        Assert::same($entities[1]->language, 'php');

        // id=3 → Manager (rank='bottom')
        Assert::instanceOf($entities[2], Manager::class);
        Assert::same($entities[2]->rank, 'bottom');

        // id=4 → Programator (engineer.level=10, programator.language='go')
        Assert::instanceOf($entities[3], Programator::class);
        Assert::same($entities[3]->level, 10);
        Assert::same($entities[3]->language, 'go');
    }

    #[Test]
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

        Assert::count($entities, 2);
        foreach ($entities as $programator) {
            Assert::instanceOf($programator, Programator::class);
            // Parent columns are populated
            Assert::false(empty($programator->name));
            Assert::notNull($programator->age);
            Assert::numeric($programator->level)->greaterThan(0);
            Assert::false(empty($programator->language));
        }
    }
}
