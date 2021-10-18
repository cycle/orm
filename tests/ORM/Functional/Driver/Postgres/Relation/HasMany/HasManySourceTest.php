<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Functional\Driver\Postgres\Relation\HasMany;

/**
 * @group driver
 * @group driver-postgres
 */
class HasManySourceTest extends \Cycle\ORM\Tests\Functional\Relation\HasMany\HasManySourceTest
{
    public const DRIVER = 'postgres';
}
