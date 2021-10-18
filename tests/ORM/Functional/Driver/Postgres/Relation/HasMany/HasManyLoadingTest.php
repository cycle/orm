<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Functional\Driver\Postgres\Relation\HasMany;

/**
 * @group driver
 * @group driver-postgres
 */
class HasManyLoadingTest extends \Cycle\ORM\Tests\Functional\Relation\HasMany\HasManyLoadingTest
{
    public const DRIVER = 'postgres';
}
