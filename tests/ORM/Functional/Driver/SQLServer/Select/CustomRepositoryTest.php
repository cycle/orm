<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Functional\Driver\SQLServer\Select;

/**
 * @group driver
 * @group driver-sqlserver
 */
class CustomRepositoryTest extends \Cycle\ORM\Tests\Functional\Select\CustomRepositoryTest
{
    public const DRIVER = 'sqlserver';
}
