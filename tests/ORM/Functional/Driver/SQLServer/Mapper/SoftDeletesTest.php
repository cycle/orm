<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Functional\Driver\SQLServer\Mapper;

/**
 * @group driver
 * @group driver-sqlserver
 */
class SoftDeletesTest extends \Cycle\ORM\Tests\Functional\Mapper\SoftDeletesTest
{
    public const DRIVER = 'sqlserver';
}
