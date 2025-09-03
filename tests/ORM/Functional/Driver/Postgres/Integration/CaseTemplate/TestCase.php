<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Functional\Driver\Postgres\Integration\CaseTemplate;

// phpcs:ignore
use Cycle\ORM\Tests\Functional\Driver\Common\Integration\CaseTemplate\TestCase as CommonClass;

/**
 * @group driver
 * @group driver-postgres
 */
class TestCase extends CommonClass
{
    public const DRIVER = 'postgres';
}
