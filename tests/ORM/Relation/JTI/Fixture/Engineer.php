<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Relation\JTI\Fixture;

final class Engineer extends Employee
{
    public ?string $id = null;
    public int $level = 0;
}
