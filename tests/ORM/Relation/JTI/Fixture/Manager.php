<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Relation\JTI\Fixture;

final class Manager extends Employee
{
    public ?int $role_id = null;

    public int $level = 0;
    public string $rank = 'none';
}
