<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Functional\Driver\Common\Inheritance\Fixture;

enum EmployeeType: string
{
    case Employee = 'employee';
    case Manager = 'manager';
}
