<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Functional\Driver\Postgres\Integration\Case1;

// phpcs:ignore
use Cycle\ORM\Tests\Functional\Driver\Common\Integration\Case1\CaseTest as CommonTest;

/**
 * @group driver
 * @group driver-postgres
 */
class CaseTest extends CommonTest
{
    public const DRIVER = 'postgres';
}
