<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Functional\Driver\MySQL\Relation\HasMany;

// phpcs:ignore
use Cycle\ORM\Tests\Functional\Driver\Common\Relation\HasMany\HasManyLoadingTest as CommonClass;

/**
 * @group driver
 * @group driver-mysql
 */
class HasManyLoadingTest extends CommonClass
{
    public const DRIVER = 'mysql';
}
