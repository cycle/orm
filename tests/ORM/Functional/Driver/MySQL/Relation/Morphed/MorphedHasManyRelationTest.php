<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Functional\Driver\MySQL\Relation\Morphed;

// phpcs:ignore
use Cycle\ORM\Tests\Functional\Driver\Common\Relation\Morphed\MorphedHasManyRelationTest as CommonClass;

/**
 * @group driver
 * @group driver-mysql
 */
class MorphedHasManyRelationTest extends CommonClass
{
    public const DRIVER = 'mysql';
}
