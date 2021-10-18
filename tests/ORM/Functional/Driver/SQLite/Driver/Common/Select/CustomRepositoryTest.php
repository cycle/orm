<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Functional\Driver\SQLite\Driver\Common\Select;

// phpcs:ignore
use Cycle\ORM\Tests\Functional\Driver\Common\Select\CustomRepositoryTest as CommonTest; 
 
/**
 * @group driver
 * @group driver-sqlite
 */
class CustomRepositoryTest extends CommonTest
{
    public const DRIVER = 'sqlite';
}
