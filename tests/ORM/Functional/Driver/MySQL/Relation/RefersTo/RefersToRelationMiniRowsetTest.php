<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Functional\Driver\MySQL\Relation\RefersTo;

// phpcs:ignore
use Cycle\ORM\Tests\Functional\Driver\Common\Relation\RefersTo\RefersToRelationMiniRowsetTest as CommonClass;

/**
 * @group driver
 * @group driver-mysql
 */
class RefersToRelationMiniRowsetTest extends CommonClass
{
    public const DRIVER = 'mysql';
}
