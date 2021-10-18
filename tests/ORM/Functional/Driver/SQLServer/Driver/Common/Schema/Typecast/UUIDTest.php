<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Functional\Driver\SQLServer\Driver\Common\Schema\Typecast;

// phpcs:ignore
use Cycle\ORM\Tests\Functional\Driver\Common\Schema\Typecast\UUIDTest as CommonTest; 
 
/**
 * @group driver
 * @group driver-sqlserver
 */
class UUIDTest extends CommonTest
{
    public const DRIVER = 'sqlserver';
}
