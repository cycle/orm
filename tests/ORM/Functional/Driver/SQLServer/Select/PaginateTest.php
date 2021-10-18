<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Functional\Driver\SQLServer\Select;

/**
 * @group driver
 * @group driver-sqlserver
 */
class PaginateTest extends \Cycle\ORM\Tests\Functional\Select\PaginateTest
{
    public const DRIVER = 'sqlserver';
}
