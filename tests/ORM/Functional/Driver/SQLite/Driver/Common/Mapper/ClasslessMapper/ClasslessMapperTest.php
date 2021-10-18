<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Functional\Driver\SQLite\Driver\Common\Mapper\ClasslessMapper;

// phpcs:ignore
use Cycle\ORM\Tests\Functional\Driver\Common\Mapper\ClasslessMapper\ClasslessMapperTest as CommonTest; 
 
/**
 * @group driver
 * @group driver-sqlite
 */
class ClasslessMapperTest extends CommonTest
{
    public const DRIVER = 'sqlite';
}
