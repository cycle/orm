<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Feature\Postgres\Inheritance\STI;

use Cycle\ORM\Select;
use Cycle\ORM\Tests\Feature\Common\Inheritance\Fixture\Employee;
use Cycle\ORM\Tests\Feature\Common\Inheritance\Fixture\Manager;
use Cycle\ORM\Tests\Feature\Common\Inheritance\STI\SimpleTest;

#[\Testo\Filter\Group('driver', 'driver-postgres')]
class CursorTest extends SimpleTest
{
    public const DRIVER = 'postgres';

    #[\Testo\Test]
    public function testCursorReturnsCorrectSubclassInstances(): void
    {
        $entities = $this->getDatabase()->transaction(function (): array {
            $out = [];
            foreach ((new Select($this->orm, Employee::class))->cursor(2) as $entity) {
                $out[] = $entity;
            }
            return $out;
        });

        \Testo\Assert::count($entities, 4);

        \Testo\Assert::instanceOf($entities[0], Manager::class);

        \Testo\Assert::instanceOf($entities[1], Employee::class);
        \Testo\Assert::false($entities[1] instanceof Manager);

        \Testo\Assert::instanceOf($entities[2], Manager::class);

        \Testo\Assert::instanceOf($entities[3], Employee::class);
        \Testo\Assert::false($entities[3] instanceof Manager);

        \Testo\Assert::same(\array_map(fn($e) => $e->id, $entities), [1, 2, 3, 4]);
    }
}
