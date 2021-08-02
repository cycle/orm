<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Relation\JTI\Fixture;

class Engineer extends Employee
{
    public int $level = 0;
    public ?Book $tech_book = null;
    public array $tools = [];
}
