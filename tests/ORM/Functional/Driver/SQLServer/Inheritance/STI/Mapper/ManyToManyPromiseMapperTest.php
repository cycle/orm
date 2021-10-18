<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Functional\Driver\SQLServer\Inheritance\STI\Mapper;

/**
 * @group driver
 * @group driver-sqlserver
 */
class ManyToManyPromiseMapperTest extends \Cycle\ORM\Tests\Functional\Inheritance\STI\Mapper\ManyToManyPromiseMapperTest
{
    public const DRIVER = 'sqlserver';
}
