<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Functional\Driver\SQLServer\Relation\HasMany\Cyclic;

// phpcs:ignore
use Cycle\ORM\Tests\Functional\Driver\Common\Relation\HasMany\Cyclic\CyclicHasManyReferencesTest as CommonClass;

/**
 * @group driver
 * @group driver-sqlserver
 */
class CyclicHasManyReferencesTest extends CommonClass
{
    public const DRIVER = 'sqlserver';
}
