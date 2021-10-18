<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Functional\Driver\SQLServer\Driver\Common\Inheritance\STI;

/**
 * @group driver
 * @group driver-sqlserver
 */
class SimpleTest extends \Cycle\ORM\Tests\Functional\Driver\Common\Inheritance\STI\SimpleTest
{
    public const DRIVER = 'sqlserver';
}
