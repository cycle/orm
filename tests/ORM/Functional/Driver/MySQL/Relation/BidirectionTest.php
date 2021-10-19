<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Functional\Driver\MySQL\Relation;

// phpcs:ignore
use Cycle\ORM\Tests\Functional\Driver\Common\Relation\BidirectionTest as CommonTest;

/**
 * @group driver
 * @group driver-mysql
 */
class BidirectionTest extends CommonTest
{
    public const DRIVER = 'mysql';
}
