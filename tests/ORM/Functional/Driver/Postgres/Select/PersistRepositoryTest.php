<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Functional\Driver\Postgres\Select;

/**
 * @group driver
 * @group driver-postgres
 */
class PersistRepositoryTest extends \Cycle\ORM\Tests\Functional\Select\PersistRepositoryTest
{
    public const DRIVER = 'postgres';
}
