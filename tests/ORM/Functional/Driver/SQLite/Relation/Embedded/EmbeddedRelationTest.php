<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Functional\Driver\SQLite\Relation\Embedded;

// phpcs:ignore
use Cycle\ORM\Tests\Functional\Driver\Common\Relation\Embedded\EmbeddedRelationTest as CommonTest;

/**
 * @group driver
 * @group driver-sqlite
 */
class EmbeddedRelationTest extends CommonTest
{
    public const DRIVER = 'sqlite';
}
