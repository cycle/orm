<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Functional\Driver\Postgres\Driver\Common\Relation\HasMany;

// phpcs:ignore
use Cycle\ORM\Tests\Functional\Driver\Common\Relation\HasMany\HasManySourceTest as CommonTest; 
 
/**
 * @group driver
 * @group driver-postgres
 */
class HasManySourceTest extends CommonTest
{
    public const DRIVER = 'postgres';
}
