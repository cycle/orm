<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Functional\Driver\SQLite;

// phpcs:ignore
use Cycle\ORM\Tests\Functional\Driver\Common\FactoryTest as CommonClass;

/**
 * @group driver
 * @group driver-sqlite
 */
class FactoryTest extends CommonClass
{
    public const DRIVER = 'sqlite';
}
