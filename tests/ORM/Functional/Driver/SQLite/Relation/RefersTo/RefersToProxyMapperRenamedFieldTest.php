<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Functional\Driver\SQLite\Relation\RefersTo;

// phpcs:ignore
use Cycle\ORM\Tests\Functional\Driver\Common\Relation\RefersTo\RefersToProxyMapperRenamedFieldTest as CommonTest;

/**
 * @group driver
 * @group driver-sqlite
 */
class RefersToProxyMapperRenamedFieldTest extends CommonTest
{
    public const DRIVER = 'sqlite';
}
