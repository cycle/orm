<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Functional\Driver\SQLServer\Inheritance\STI;

/**
 * @group driver
 * @group driver-sqlserver
 */
class ManyToManyTest extends \Cycle\ORM\Tests\Functional\Inheritance\STI\ManyToManyTest
{
    public const DRIVER = 'sqlserver';
}
