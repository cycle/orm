<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Functional\Driver\SQLite\Relation\Morphed;

// phpcs:ignore
use Cycle\ORM\Tests\Functional\Driver\Common\Relation\Morphed\MorphedHasOneLoadOptionsTest as CommonClass;

/**
 * @group driver
 * @group driver-sqlite
 */
class MorphedHasOneLoadOptionsTest extends CommonClass
{
    public const DRIVER = 'sqlite';
}
