<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Functional\Driver\SQLite\Integration\Case321;

// phpcs:ignore
use Cycle\ORM\Tests\Functional\Driver\Common\Integration\Case321\CaseTest as CommonClass;

/**
 * @group driver
 * @group driver-sqlite
 */
class CaseTest extends CommonClass
{
    public const DRIVER = 'sqlite';
}
