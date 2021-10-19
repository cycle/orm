<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Functional\Driver\SQLite\Mapper;

// phpcs:ignore
use Cycle\ORM\Tests\Functional\Driver\Common\Mapper\SoftDeletesTest as CommonTest;

/**
 * @group driver
 * @group driver-sqlite
 */
class SoftDeletesTest extends CommonTest
{
    public const DRIVER = 'sqlite';
}
