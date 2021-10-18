<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Functional\Driver\MySQL\Driver\Common\Relation\HasMany\Cyclic;

// phpcs:ignore
use Cycle\ORM\Tests\Functional\Driver\Common\Relation\HasMany\Cyclic\CyclicHasManyReferencesTest as CommonTest;

/**
 * @group driver
 * @group driver-mysql
 */
class CyclicHasManyReferencesTest extends CommonTest
{
    public const DRIVER = 'mysql';
}
