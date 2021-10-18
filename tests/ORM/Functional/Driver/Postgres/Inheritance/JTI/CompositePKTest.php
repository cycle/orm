<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Functional\Driver\Postgres\Inheritance\JTI;

/**
 * @group driver
 * @group driver-postgres
 */
class CompositePKTest extends \Cycle\ORM\Tests\Functional\Inheritance\JTI\CompositePKTest
{
    public const DRIVER = 'postgres';
}
