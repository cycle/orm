<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Functional\Driver\SQLite\Select;

// phpcs:ignore
use Cycle\ORM\Tests\Functional\Driver\Common\Select\QueryBuilderTest as CommonTest;

/**
 * @group driver
 * @group driver-sqlite
 */
class QueryBuilderTest extends CommonTest
{
    public const DRIVER = 'sqlite';
}
