<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Functional\Driver\SQLServer\Relation\HasMany\Cyclic;

/**
 * @group driver
 * @group driver-sqlserver
 */
class CyclicHasManyReferencesWithCompositePKTest extends \Cycle\ORM\Tests\Functional\Relation\HasMany\Cyclic\CyclicHasManyReferencesWithCompositePKTest
{
    public const DRIVER = 'sqlserver';
}
