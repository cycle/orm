<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Functional\Driver\Postgres\Driver\Common\Relation\HasOne;

// phpcs:ignore
use Cycle\ORM\Tests\Functional\Driver\Common\Relation\HasOne\HasOneCyclicTest as CommonTest;

/**
 * @group driver
 * @group driver-postgres
 */
class HasOneCyclicTest extends CommonTest
{
    public const DRIVER = 'postgres';
}
