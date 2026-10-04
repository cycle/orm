<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Functional\Driver\Postgres\Integration\Issue71;

// phpcs:ignore
use Cycle\ORM\Tests\Functional\Driver\Common\Integration\Issue71\CaseTest as CommonClass;

/**
 * @group driver
 * @group driver-postgres
 */
class CaseTest extends CommonClass
{
    public const DRIVER = 'postgres';
}
