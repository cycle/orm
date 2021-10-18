<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Functional\Driver\MySQL\Driver\Common\Relation\Morphed;

// phpcs:ignore
use Cycle\ORM\Tests\Functional\Driver\Common\Relation\Morphed\MorphedHasOneRelationTest as CommonTest;

/**
 * @group driver
 * @group driver-mysql
 */
class MorphedHasOneRelationTest extends CommonTest
{
    public const DRIVER = 'mysql';
}
