<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Functional\Driver\Postgres\Driver\Common\Relation\HasMany;

// phpcs:ignore
use Cycle\ORM\Tests\Functional\Driver\Common\Relation\HasMany\HasManyProxyMapperTest as CommonTest; 
 
/**
 * @group driver
 * @group driver-postgres
 */
class HasManyProxyMapperTest extends CommonTest
{
    public const DRIVER = 'postgres';
}
