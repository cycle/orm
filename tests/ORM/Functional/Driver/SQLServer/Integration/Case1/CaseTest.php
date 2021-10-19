<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Functional\Driver\SQLServer\Integration\Case1;

// phpcs:ignore
use Cycle\ORM\Tests\Functional\Driver\Common\Integration\Case1\CaseTest as CommonTest;

/**
 * @group driver
 * @group driver-sqlserver
 */
class CaseTest extends CommonTest
{
    public const DRIVER = 'sqlserver';
}
