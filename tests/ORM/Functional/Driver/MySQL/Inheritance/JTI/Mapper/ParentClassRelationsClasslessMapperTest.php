<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Functional\Driver\MySQL\Inheritance\JTI\Mapper;

// phpcs:ignore
use Cycle\ORM\Tests\Functional\Driver\Common\Inheritance\JTI\Mapper\ParentClassRelationsClasslessMapperTest as CommonClass;

/**
 * @group driver
 * @group driver-mysql
 */
class ParentClassRelationsClasslessMapperTest extends CommonClass
{
    public const DRIVER = 'mysql';
}
