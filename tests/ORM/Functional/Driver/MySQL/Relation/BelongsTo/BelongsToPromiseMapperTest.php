<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Functional\Driver\MySQL\Relation\BelongsTo;

// phpcs:ignore
use Cycle\ORM\Tests\Functional\Driver\Common\Relation\BelongsTo\BelongsToPromiseMapperTest as CommonTest;

/**
 * @group driver
 * @group driver-mysql
 */
class BelongsToPromiseMapperTest extends CommonTest
{
    public const DRIVER = 'mysql';
}
