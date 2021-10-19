<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Functional\Driver\SQLite\Schema;

// phpcs:ignore
use Cycle\ORM\Tests\Functional\Driver\Common\Schema\TableRendererTest as CommonTest;

/**
 * @group driver
 * @group driver-sqlite
 */
class TableRendererTest extends CommonTest
{
    public const DRIVER = 'sqlite';
}
