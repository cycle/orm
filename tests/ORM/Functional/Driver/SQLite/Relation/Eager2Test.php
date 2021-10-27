<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Functional\Driver\SQLite\Relation;

// phpcs:ignore
use Cycle\ORM\Tests\Functional\Driver\Common\Relation\Eager2Test as CommonClass;

/**
 * @group driver
 * @group driver-sqlite
 */
class Eager2Test extends CommonClass
{
    public const DRIVER = 'sqlite';
}
