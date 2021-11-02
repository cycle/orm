<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Functional\Driver\Postgres\Relation\HasMany;

// phpcs:ignore
use Cycle\ORM\Tests\Functional\Driver\Common\Relation\HasMany\HasManyProxyMapperTest as CommonClass;

/**
 * @group driver
 * @group driver-postgres
 */
class HasManyProxyMapperTest extends CommonClass
{
    public const DRIVER = 'postgres';
}
