<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Functional\Driver\Common\Inheritance\Fixture;

use Cycle\ORM\Reference\ReferenceInterface;

class Engineer extends Employee
{
    public ?int $role_id = null;

    public int $level = 0;
    public ?string $rank = null;
    public null|Book|ReferenceInterface $tech_book = null;
    public array|ReferenceInterface $tools = [];
}
