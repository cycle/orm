<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Functional\Driver\MySQL\Relation;

// phpcs:ignore
use Cycle\ORM\Tests\Functional\Driver\Common\Relation\ExistingNestedRelationTest as CommonClass;

/**
 * @group driver
 * @group driver-mysql
 */
class ExistingNestedRelationTest extends CommonClass
{
    public const DRIVER = 'mysql';
}
