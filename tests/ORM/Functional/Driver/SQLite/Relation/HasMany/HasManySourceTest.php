<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Functional\Driver\SQLite\Relation\HasMany;

// phpcs:ignore
use Cycle\ORM\Tests\Functional\Driver\Common\Relation\HasMany\HasManySourceTest as CommonClass;

/**
 * @group driver
 * @group driver-sqlite
 */
class HasManySourceTest extends CommonClass
{
    public const DRIVER = 'sqlite';
}
