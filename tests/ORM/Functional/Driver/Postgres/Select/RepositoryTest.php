<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Functional\Driver\Postgres\Select;

// phpcs:ignore
use Cycle\ORM\Tests\Functional\Driver\Common\Select\RepositoryTest as CommonClass;

/**
 * @group driver
 * @group driver-postgres
 */
class RepositoryTest extends CommonClass
{
    public const DRIVER = 'postgres';
}
