<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Relation\JTI\Fixture;

use Cycle\ORM\Reference\ReferenceInterface;

class Employee
{
    public ?int $id = null;
    public ?int $employee_id = null;

    public ?string $name = null;
    public null|Book|ReferenceInterface $book = null;
}
