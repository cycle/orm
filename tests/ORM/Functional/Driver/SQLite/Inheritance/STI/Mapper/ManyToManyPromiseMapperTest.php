<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Functional\Driver\SQLite\Inheritance\STI\Mapper;

// phpcs:ignore
use Cycle\ORM\Tests\Functional\Driver\Common\Inheritance\STI\Mapper\ManyToManyPromiseMapperTest as CommonClass;

/**
 * @group driver
 * @group driver-sqlite
 */
class ManyToManyPromiseMapperTest extends CommonClass
{
    public const DRIVER = 'sqlite';
}
