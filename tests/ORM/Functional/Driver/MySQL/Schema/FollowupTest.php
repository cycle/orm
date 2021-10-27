<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Functional\Driver\MySQL\Schema;

// phpcs:ignore
use Cycle\ORM\Tests\Functional\Driver\Common\Schema\FollowupTest as CommonClass;

/**
 * @group driver
 * @group driver-mysql
 */
class FollowupTest extends CommonClass
{
    public const DRIVER = 'mysql';
}
