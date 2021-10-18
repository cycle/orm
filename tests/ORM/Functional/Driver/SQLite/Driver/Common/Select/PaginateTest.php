<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Functional\Driver\SQLite\Driver\Common\Select;

// phpcs:ignore
use Cycle\ORM\Tests\Functional\Driver\Common\Select\PaginateTest as CommonTest; 
 
/**
 * @group driver
 * @group driver-sqlite
 */
class PaginateTest extends CommonTest
{
    public const DRIVER = 'sqlite';
}
