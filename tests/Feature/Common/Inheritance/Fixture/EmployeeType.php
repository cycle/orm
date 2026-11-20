<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Feature\Common\Inheritance\Fixture;

enum EmployeeType: string
{
    case Employee = 'employee';
    case Manager = 'manager';
}
