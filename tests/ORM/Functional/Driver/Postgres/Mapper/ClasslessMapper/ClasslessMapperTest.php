<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Functional\Driver\Postgres\Mapper\ClasslessMapper;

// phpcs:ignore
use Cycle\ORM\Tests\Functional\Driver\Common\Mapper\ClasslessMapper\ClasslessMapperTest as CommonClass;

/**
 * @group driver
 * @group driver-postgres
 */
class ClasslessMapperTest extends CommonClass
{
    public const DRIVER = 'postgres';
}
