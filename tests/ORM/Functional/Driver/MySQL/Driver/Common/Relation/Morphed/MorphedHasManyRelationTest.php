<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Functional\Driver\MySQL\Driver\Common\Relation\Morphed;

// phpcs:ignore
use Cycle\ORM\Tests\Functional\Driver\Common\Relation\Morphed\MorphedHasManyRelationTest as CommonTest;

/**
 * @group driver
 * @group driver-mysql
 */
class MorphedHasManyRelationTest extends CommonTest
{
    public const DRIVER = 'mysql';
}
