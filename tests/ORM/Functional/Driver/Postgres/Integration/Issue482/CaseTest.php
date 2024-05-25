<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Functional\Driver\Postgres\Integration\Issue482;

// phpcs:ignore
use Cycle\ORM\Tests\Functional\Driver\Common\Integration\Issue482\AbstractTestCase;

/**
 * @group driver
 * @group driver-postgres
 */
class CaseTest extends AbstractTestCase
{
    public const DRIVER = 'postgres';
}
