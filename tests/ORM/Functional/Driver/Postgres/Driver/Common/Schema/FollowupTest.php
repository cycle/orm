<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Functional\Driver\Postgres\Driver\Common\Schema;

// phpcs:ignore
use Cycle\ORM\Tests\Functional\Driver\Common\Schema\FollowupTest as CommonTest; 
 
/**
 * @group driver
 * @group driver-postgres
 */
class FollowupTest extends CommonTest
{
    public const DRIVER = 'postgres';
}
