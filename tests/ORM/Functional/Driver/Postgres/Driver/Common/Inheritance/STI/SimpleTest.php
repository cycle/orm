<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Functional\Driver\Postgres\Driver\Common\Inheritance\STI;

/**
 * @group driver
 * @group driver-postgres
 */
class SimpleTest extends \Cycle\ORM\Tests\Functional\Driver\Common\Inheritance\STI\SimpleTest
{
    public const DRIVER = 'postgres';
}
