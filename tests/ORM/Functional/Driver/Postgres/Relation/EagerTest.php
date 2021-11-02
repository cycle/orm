<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Functional\Driver\Postgres\Relation;

// phpcs:ignore
use Cycle\ORM\Tests\Functional\Driver\Common\Relation\EagerTest as CommonClass;

/**
 * @group driver
 * @group driver-postgres
 */
class EagerTest extends CommonClass
{
    public const DRIVER = 'postgres';
}
