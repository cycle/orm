<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Functional\Driver\SQLite\Select;

/**
 * @group driver
 * @group driver-sqlite
 */
class PaginateTest extends \Cycle\ORM\Tests\Functional\Select\PaginateTest
{
    public const DRIVER = 'sqlite';
}
