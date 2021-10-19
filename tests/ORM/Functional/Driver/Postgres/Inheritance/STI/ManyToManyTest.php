<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Functional\Driver\Postgres\Inheritance\STI;

// phpcs:ignore
use Cycle\ORM\Tests\Functional\Driver\Common\Inheritance\STI\ManyToManyTest as CommonTest;

/**
 * @group driver
 * @group driver-postgres
 */
class ManyToManyTest extends CommonTest
{
    public const DRIVER = 'postgres';
}
