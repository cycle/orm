<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Functional\Inheritance\Fixture;

class Manager extends Employee
{
    public ?int $role_id = null;

    public ?int $level = null;
    public string $rank = 'none';
}
