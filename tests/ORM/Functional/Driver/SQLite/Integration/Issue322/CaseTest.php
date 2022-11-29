<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Functional\Driver\SQLite\Integration\Issue322;

// phpcs:ignore
use Cycle\ORM\Tests\Functional\Driver\Common\Integration\Issue322\CaseTest as CommonClass;

/**
 * @group driver
 * @group driver-sqlite
 */
class CaseTest extends CommonClass
{
    public const DRIVER = 'sqlite';
}
