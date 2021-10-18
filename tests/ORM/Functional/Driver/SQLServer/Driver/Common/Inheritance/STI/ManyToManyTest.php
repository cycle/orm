<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Functional\Driver\SQLServer\Driver\Common\Inheritance\STI;

// phpcs:ignore
use Cycle\ORM\Tests\Functional\Driver\Common\Inheritance\STI\ManyToManyTest as CommonTest; 
 
/**
 * @group driver
 * @group driver-sqlserver
 */
class ManyToManyTest extends CommonTest
{
    public const DRIVER = 'sqlserver';
}
