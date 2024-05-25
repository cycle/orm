<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Functional\Driver\SQLServer\Integration\Issue482;

// phpcs:ignore
use Cycle\ORM\Select;
use Cycle\ORM\Tests\Functional\Driver\Common\Integration\Issue482\AbstractTestCase;

/**
 * @group driver
 * @group driver-sqlserver
 */
class CaseTest extends AbstractTestCase
{
    public const DRIVER = 'sqlserver';

    protected function assertExpectedSql(Select $select): void
    {
    }
}
