<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Functional\Driver\SQLServer\Driver\Common\Relation\Morphed;

// phpcs:ignore
use Cycle\ORM\Tests\Functional\Driver\Common\Relation\Morphed\MorphedHasManyRelationTest as CommonTest;

/**
 * @group driver
 * @group driver-sqlserver
 */
class MorphedHasManyRelationTest extends CommonTest
{
    public const DRIVER = 'sqlserver';
}
