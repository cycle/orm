<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Functional\Driver\Postgres\Driver\Common\Relation\HasOne;

// phpcs:ignore
use Cycle\ORM\Tests\Functional\Driver\Common\Relation\HasOne\HasOneCompositeKeyTest as CommonTest;

/**
 * @group driver
 * @group driver-postgres
 */
class HasOneCompositeKeyTest extends CommonTest
{
    public const DRIVER = 'postgres';
}
