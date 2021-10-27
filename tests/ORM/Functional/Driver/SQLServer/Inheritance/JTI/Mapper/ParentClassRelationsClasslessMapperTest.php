<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Functional\Driver\SQLServer\Inheritance\JTI\Mapper;

// phpcs:ignore
use Cycle\ORM\Tests\Functional\Driver\Common\Inheritance\JTI\Mapper\ParentClassRelationsClasslessMapperTest as CommonClass;

/**
 * @group driver
 * @group driver-sqlserver
 */
class ParentClassRelationsClasslessMapperTest extends CommonClass
{
    public const DRIVER = 'sqlserver';
}
