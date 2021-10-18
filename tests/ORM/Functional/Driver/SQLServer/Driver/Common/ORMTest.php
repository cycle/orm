<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Functional\Driver\SQLServer\Driver\Common;

// phpcs:ignore
use Cycle\ORM\Tests\Functional\Driver\Common\ORMTest as CommonTest; 
 
/**
 * @group driver
 * @group driver-sqlserver
 */
class ORMTest extends CommonTest
{
    public const DRIVER = 'sqlserver';
}
