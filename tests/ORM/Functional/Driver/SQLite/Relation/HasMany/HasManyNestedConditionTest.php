<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Functional\Driver\SQLite\Relation\HasMany;

/**
 * @group driver
 * @group driver-sqlite
 */
class HasManyNestedConditionTest extends \Cycle\ORM\Tests\Functional\Relation\HasMany\HasManyNestedConditionTest
{
    public const DRIVER = 'sqlite';
}
