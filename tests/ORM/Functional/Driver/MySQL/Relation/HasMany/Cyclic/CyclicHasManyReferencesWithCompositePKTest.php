<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Functional\Driver\MySQL\Relation\HasMany\Cyclic;

/**
 * @group driver
 * @group driver-mysql
 */
class CyclicHasManyReferencesWithCompositePKTest extends \Cycle\ORM\Tests\Functional\Relation\HasMany\Cyclic\CyclicHasManyReferencesWithCompositePKTest
{
    public const DRIVER = 'mysql';
}
