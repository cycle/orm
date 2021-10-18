<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Functional\Driver\SQLite\Mapper;

/**
 * @group driver
 * @group driver-sqlite
 */
class SoftDeletesTest extends \Cycle\ORM\Tests\Functional\Mapper\SoftDeletesTest
{
    public const DRIVER = 'sqlite';
}
