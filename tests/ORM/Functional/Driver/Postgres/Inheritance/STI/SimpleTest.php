<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Functional\Driver\Postgres\Inheritance\STI;

/**
 * @group driver
 * @group driver-postgres
 */
class SimpleTest extends \Cycle\ORM\Tests\Functional\Inheritance\STI\SimpleTest
{
    public const DRIVER = 'postgres';
}
