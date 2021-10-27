<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Functional\Driver\SQLite\Mapper;

// phpcs:ignore
use Cycle\ORM\Tests\Functional\Driver\Common\Mapper\UUIDTest as CommonClass;

/**
 * @group driver
 * @group driver-sqlite
 */
class UUIDTest extends CommonClass
{
    public const DRIVER = 'sqlite';
}
