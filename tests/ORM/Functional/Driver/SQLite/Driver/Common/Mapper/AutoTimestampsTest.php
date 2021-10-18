<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Functional\Driver\SQLite\Driver\Common\Mapper;

// phpcs:ignore
use Cycle\ORM\Tests\Functional\Driver\Common\Mapper\AutoTimestampsTest as CommonTest; 
 
/**
 * @group driver
 * @group driver-sqlite
 */
class AutoTimestampsTest extends CommonTest
{
    public const DRIVER = 'sqlite';
}
