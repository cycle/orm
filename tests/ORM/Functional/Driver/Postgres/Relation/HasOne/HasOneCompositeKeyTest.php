<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Functional\Driver\Postgres\Relation\HasOne;

// phpcs:ignore
use Cycle\ORM\Tests\Functional\Driver\Common\Relation\HasOne\HasOneCompositeKeyTest as CommonClass;

/**
 * @group driver
 * @group driver-postgres
 */
class HasOneCompositeKeyTest extends CommonClass
{
    public const DRIVER = 'postgres';
}
