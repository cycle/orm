<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Functional\Driver\Postgres\Inheritance\JTI\Mapper;

// phpcs:ignore
use Cycle\ORM\Tests\Functional\Driver\Common\Inheritance\JTI\Mapper\ParentClassRelationsStdMapperTest as CommonTest;

/**
 * @group driver
 * @group driver-postgres
 */
class ParentClassRelationsStdMapperTest extends CommonTest
{
    public const DRIVER = 'postgres';
}
