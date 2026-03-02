<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Functional\Driver\SQLServer\Integration\Case430;

// phpcs:ignore
use Cycle\ORM\Tests\Functional\Driver\Common\Integration\Case430\CaseTest as CommonClass;

/**
 * @group driver
 * @group driver-sqlserver
 */
class CaseTest extends CommonClass
{
    public const DRIVER = 'sqlserver';
}
