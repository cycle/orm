<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Functional\Driver\MySQL\Inheritance\JTI\Mapper;

// phpcs:ignore
use Cycle\ORM\Tests\Functional\Driver\Common\Inheritance\JTI\Mapper\ParentClassRelationsStdMapperTest as CommonTest;

/**
 * @group driver
 * @group driver-mysql
 */
class ParentClassRelationsStdMapperTest extends CommonTest
{
    public const DRIVER = 'mysql';
}
