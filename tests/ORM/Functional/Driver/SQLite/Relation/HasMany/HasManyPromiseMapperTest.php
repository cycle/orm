<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Functional\Driver\SQLite\Relation\HasMany;

/**
 * @group driver
 * @group driver-sqlite
 */
class HasManyPromiseMapperTest extends \Cycle\ORM\Tests\Functional\Relation\HasMany\HasManyPromiseMapperTest
{
    public const DRIVER = 'sqlite';
}
