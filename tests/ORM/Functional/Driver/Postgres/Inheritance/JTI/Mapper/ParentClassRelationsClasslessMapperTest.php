<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Functional\Driver\Postgres\Inheritance\JTI\Mapper;

// phpcs:ignore
use Cycle\ORM\Tests\Functional\Driver\Common\Inheritance\JTI\Mapper\ParentClassRelationsClasslessMapperTest as CommonClass;

/**
 * @group driver
 * @group driver-postgres
 */
class ParentClassRelationsClasslessMapperTest extends CommonClass
{
    public const DRIVER = 'postgres';
}
