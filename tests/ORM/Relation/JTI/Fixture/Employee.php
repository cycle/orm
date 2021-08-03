<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Relation\JTI\Fixture;

class Employee
{
    public ?int $id = null;
    public ?int $employee_id = null;

    public ?string $name = null;
    public ?Book $book = null;
}
