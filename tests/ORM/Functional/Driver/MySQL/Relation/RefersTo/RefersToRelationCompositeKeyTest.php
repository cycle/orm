<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Functional\Driver\MySQL\Relation\RefersTo;

// phpcs:ignore
use Cycle\ORM\Tests\Functional\Driver\Common\Relation\RefersTo\RefersToRelationCompositeKeyTest as CommonClass;

/**
 * @group driver
 * @group driver-mysql
 */
class RefersToRelationCompositeKeyTest extends CommonClass
{
    public const DRIVER = 'mysql';
}
