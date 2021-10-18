<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Functional\Driver\Postgres\Relation\HasMany;

/**
 * @group driver
 * @group driver-postgres
 */
class HasManyCompositeKeyTest extends \Cycle\ORM\Tests\Functional\Relation\HasMany\HasManyCompositeKeyTest
{
    public const DRIVER = 'postgres';
}
