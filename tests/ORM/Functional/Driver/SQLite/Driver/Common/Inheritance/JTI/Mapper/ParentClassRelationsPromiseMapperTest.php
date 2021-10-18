<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Functional\Driver\SQLite\Driver\Common\Inheritance\JTI\Mapper;

// phpcs:ignore
use Cycle\ORM\Tests\Functional\Driver\Common\Inheritance\JTI\Mapper\ParentClassRelationsPromiseMapperTest as CommonTest;

/**
 * @group driver
 * @group driver-sqlite
 */
class ParentClassRelationsPromiseMapperTest extends CommonTest
{
    public const DRIVER = 'sqlite';
}
