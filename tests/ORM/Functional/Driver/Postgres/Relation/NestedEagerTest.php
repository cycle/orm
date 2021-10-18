<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Functional\Driver\Postgres\Relation;

/**
 * @group driver
 * @group driver-postgres
 */
class NestedEagerTest extends \Cycle\ORM\Tests\Functional\Relation\NestedEagerTest
{
    public const DRIVER = 'postgres';
}
