<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Functional\Driver\SQLite\Driver\Common\Inheritance\STI\Mapper;

// phpcs:ignore
use Cycle\ORM\Tests\Functional\Driver\Common\Inheritance\STI\Mapper\ManyToManyPromiseMapperTest as CommonTest; 
 
/**
 * @group driver
 * @group driver-sqlite
 */
class ManyToManyPromiseMapperTest extends CommonTest
{
    public const DRIVER = 'sqlite';
}
