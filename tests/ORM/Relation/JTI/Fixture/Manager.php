<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Relation\JTI\Fixture;

final class Manager extends Employee
{
    public ?string $id = null;
    public string $rank = 'none';
}
