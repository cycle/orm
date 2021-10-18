<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Functional\Driver\SQLServer\Driver\Common\Inheritance\JTI\Mapper;

// phpcs:ignore
use Cycle\ORM\Tests\Functional\Driver\Common\Inheritance\JTI\Mapper\ParentClassRelationsClasslessMapperTest as CommonTest; 
 
/**
 * @group driver
 * @group driver-sqlserver
 */
class ParentClassRelationsClasslessMapperTest extends CommonTest
{
    public const DRIVER = 'sqlserver';
}
