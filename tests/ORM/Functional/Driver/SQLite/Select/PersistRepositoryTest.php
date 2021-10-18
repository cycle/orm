<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Functional\Driver\SQLite\Select;

/**
 * @group driver
 * @group driver-sqlite
 */
class PersistRepositoryTest extends \Cycle\ORM\Tests\Functional\Select\PersistRepositoryTest
{
    public const DRIVER = 'sqlite';
}
