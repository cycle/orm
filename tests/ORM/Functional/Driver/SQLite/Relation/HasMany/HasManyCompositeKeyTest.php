<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Functional\Driver\SQLite\Relation\HasMany;

/**
 * @group driver
 * @group driver-sqlite
 */
class HasManyCompositeKeyTest extends \Cycle\ORM\Tests\Functional\Relation\HasMany\HasManyCompositeKeyTest
{
    public const DRIVER = 'sqlite';
}
