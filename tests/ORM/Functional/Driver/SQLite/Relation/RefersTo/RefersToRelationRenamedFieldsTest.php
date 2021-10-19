<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Functional\Driver\SQLite\Relation\RefersTo;

// phpcs:ignore
use Cycle\ORM\Tests\Functional\Driver\Common\Relation\RefersTo\RefersToRelationRenamedFieldsTest as CommonTest;

/**
 * @group driver
 * @group driver-sqlite
 */
class RefersToRelationRenamedFieldsTest extends CommonTest
{
    public const DRIVER = 'sqlite';
}
