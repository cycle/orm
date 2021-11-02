<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Functional\Driver\SQLServer\Relation\Morphed;

// phpcs:ignore
use Cycle\ORM\Tests\Functional\Driver\Common\Relation\Morphed\MorphedHasManyScopeTest as CommonClass;

/**
 * @group driver
 * @group driver-sqlserver
 */
class MorphedHasManyScopeTest extends CommonClass
{
    public const DRIVER = 'sqlserver';
}
