<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Functional\Driver\SQLite\Inheritance\JTI\Mapper;

// phpcs:ignore
use Cycle\ORM\Tests\Functional\Driver\Common\Inheritance\JTI\Mapper\ParentClassRelationsStdMapperTest as CommonClass;

/**
 * @group driver
 * @group driver-sqlite
 */
class ParentClassRelationsStdMapperTest extends CommonClass
{
    public const DRIVER = 'sqlite';
}
