<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Functional\Driver\SQLite\Integration\Case1;

// phpcs:ignore
use Cycle\ORM\Tests\Functional\Driver\Common\Integration\Case1\CaseTest as CommonTest;

/**
 * @group driver
 * @group driver-sqlite
 */
class CaseTest extends CommonTest
{
    public const DRIVER = 'sqlite';
}
