<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Functional\Driver\SQLServer\Inheritance\STI;

/**
 * @group driver
 * @group driver-sqlserver
 */
class SimpleTest extends \Cycle\ORM\Tests\Functional\Inheritance\STI\SimpleTest
{
    public const DRIVER = 'sqlserver';
}
