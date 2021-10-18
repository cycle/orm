<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Functional\Driver\SQLite\Relation\Morphed;

/**
 * @group driver
 * @group driver-sqlite
 */
class MorphedHasOnePromiseTest extends \Cycle\ORM\Tests\Functional\Relation\Morphed\MorphedHasOnePromiseTest
{
    public const DRIVER = 'sqlite';
}
