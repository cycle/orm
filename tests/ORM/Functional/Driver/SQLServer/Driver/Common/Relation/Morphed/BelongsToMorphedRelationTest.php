<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Functional\Driver\SQLServer\Driver\Common\Relation\Morphed;

// phpcs:ignore
use Cycle\ORM\Tests\Functional\Driver\Common\Relation\Morphed\BelongsToMorphedRelationTest as CommonTest; 
 
/**
 * @group driver
 * @group driver-sqlserver
 */
class BelongsToMorphedRelationTest extends CommonTest
{
    public const DRIVER = 'sqlserver';
}
