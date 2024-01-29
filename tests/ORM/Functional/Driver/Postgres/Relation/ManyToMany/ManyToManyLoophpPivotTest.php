<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Functional\Driver\Postgres\Relation\ManyToMany;

// phpcs:ignore
use Cycle\ORM\Tests\Functional\Driver\Common\Relation\ManyToMany\ManyToManyLoophpPivotTest as CommonClass;

/**
 * @group driver
 * @group driver-postgres
 */
class ManyToManyLoophpPivotTest extends CommonClass
{
    public const DRIVER = 'postgres';
}
