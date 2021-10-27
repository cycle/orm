<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Functional\Driver\Postgres\Relation\HasMany;

// phpcs:ignore
use Cycle\ORM\Tests\Functional\Driver\Common\Relation\HasMany\HasManyRelationTest as CommonClass;

/**
 * @group driver
 * @group driver-postgres
 */
class HasManyRelationTest extends CommonClass
{
    public const DRIVER = 'postgres';
}
