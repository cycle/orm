<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Functional\Driver\MySQL\Relation\HasMany;

// phpcs:ignore
use Cycle\ORM\Tests\Functional\Driver\Common\Relation\HasMany\HasManySourceTest as CommonClass;

/**
 * @group driver
 * @group driver-mysql
 */
class HasManySourceTest extends CommonClass
{
    public const DRIVER = 'mysql';
}
