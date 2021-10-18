<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Functional\Driver\SQLServer\Driver\Common\Relation;

// phpcs:ignore
use Cycle\ORM\Tests\Functional\Driver\Common\Relation\LinkedTreeTest as CommonTest; 
 
/**
 * @group driver
 * @group driver-sqlserver
 */
class LinkedTreeTest extends CommonTest
{
    public const DRIVER = 'sqlserver';
}
