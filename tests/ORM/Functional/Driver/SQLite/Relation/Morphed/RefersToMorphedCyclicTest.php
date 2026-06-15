<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Functional\Driver\SQLite\Relation\Morphed;

// phpcs:ignore
use Cycle\ORM\Tests\Functional\Driver\Common\Relation\Morphed\RefersToMorphedCyclicTest as CommonClass;

/**
 * @group driver
 * @group driver-sqlite
 */
class RefersToMorphedCyclicTest extends CommonClass
{
    public const DRIVER = 'sqlite';
}
