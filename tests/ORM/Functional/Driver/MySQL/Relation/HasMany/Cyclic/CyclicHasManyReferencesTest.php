<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Functional\Driver\MySQL\Relation\HasMany\Cyclic;

/**
 * @group driver
 * @group driver-mysql
 */
class CyclicHasManyReferencesTest extends \Cycle\ORM\Tests\Functional\Relation\HasMany\Cyclic\CyclicHasManyReferencesTest
{
    public const DRIVER = 'mysql';
}
