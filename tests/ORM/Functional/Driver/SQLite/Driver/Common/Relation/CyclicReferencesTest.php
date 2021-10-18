<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Functional\Driver\SQLite\Driver\Common\Relation;

// phpcs:ignore
use Cycle\ORM\Tests\Functional\Driver\Common\Relation\CyclicReferencesTest as CommonTest;

/**
 * @group driver
 * @group driver-sqlite
 */
class CyclicReferencesTest extends CommonTest
{
    public const DRIVER = 'sqlite';
}
