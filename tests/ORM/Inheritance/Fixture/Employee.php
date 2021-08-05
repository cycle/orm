<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Inheritance\Fixture;

use Cycle\ORM\Reference\ReferenceInterface;

class Employee
{
    public ?int $id = null;
    public ?int $employee_id = null;

    public ?string $name = null;
    public ?string $email = null;
    public ?int $age = 0;

    public null|Book|ReferenceInterface $book = null;
}
