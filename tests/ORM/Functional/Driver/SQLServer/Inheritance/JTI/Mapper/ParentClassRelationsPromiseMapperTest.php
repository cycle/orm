<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Functional\Driver\SQLServer\Inheritance\JTI\Mapper;

// phpcs:ignore
use Cycle\ORM\Tests\Functional\Driver\Common\Inheritance\JTI\Mapper\ParentClassRelationsPromiseMapperTest as CommonClass;

/**
 * @group driver
 * @group driver-sqlserver
 */
class ParentClassRelationsPromiseMapperTest extends CommonClass
{
    public const DRIVER = 'sqlserver';
}
