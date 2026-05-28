<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Functional\Driver\Common\Inheritance\Fixture;

class WorkerWithKind extends Human
{
    public ?EmployeeType $type = null;
    public ?string $name = null;
    public ?string $email = null;
    public ?int $age = 0;
}
