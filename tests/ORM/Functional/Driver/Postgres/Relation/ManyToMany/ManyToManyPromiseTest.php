<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Functional\Driver\Postgres\Relation\ManyToMany;

// phpcs:ignore
use Cycle\ORM\Tests\Functional\Driver\Common\Relation\ManyToMany\ManyToManyPromiseTest as CommonTest;

/**
 * @group driver
 * @group driver-postgres
 */
class ManyToManyPromiseTest extends CommonTest
{
    public const DRIVER = 'postgres';
}
