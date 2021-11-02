<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Functional\Driver\MySQL\Relation\BelongsTo;

// phpcs:ignore
use Cycle\ORM\Tests\Functional\Driver\Common\Relation\BelongsTo\BelongsToWithHasOneTest as CommonClass;

/**
 * @group driver
 * @group driver-mysql
 */
class BelongsToWithHasOneTest extends CommonClass
{
    public const DRIVER = 'mysql';
}
