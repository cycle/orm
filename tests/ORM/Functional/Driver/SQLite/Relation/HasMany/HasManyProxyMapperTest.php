<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Functional\Driver\SQLite\Relation\HasMany;

// phpcs:ignore
use Cycle\ORM\Tests\Functional\Driver\Common\Relation\HasMany\HasManyProxyMapperTest as CommonClass;

/**
 * @group driver
 * @group driver-sqlite
 */
class HasManyProxyMapperTest extends CommonClass
{
    public const DRIVER = 'sqlite';
}
