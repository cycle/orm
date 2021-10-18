<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Functional\Driver\Postgres\Select;

/**
 * @group driver
 * @group driver-postgres
 */
class CustomRepositoryTest extends \Cycle\ORM\Tests\Functional\Select\CustomRepositoryTest
{
    public const DRIVER = 'postgres';
}
