<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Functional\Driver\SQLite\EntityManager;

// phpcs:ignore
use Cycle\ORM\Tests\Functional\Driver\Common\EntityManager\EntityManagerTest as CommonClass;

/**
 * @group driver
 * @group driver-sqlite
 */
class EntityManagerTest extends CommonClass
{
    public const DRIVER = 'sqlite';
}
