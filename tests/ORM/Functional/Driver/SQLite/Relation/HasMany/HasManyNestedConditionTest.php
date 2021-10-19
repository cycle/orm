<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Functional\Driver\SQLite\Relation\HasMany;

// phpcs:ignore
use Cycle\ORM\Tests\Functional\Driver\Common\Relation\HasMany\HasManyNestedConditionTest as CommonTest;

/**
 * @group driver
 * @group driver-sqlite
 */
class HasManyNestedConditionTest extends CommonTest
{
    public const DRIVER = 'sqlite';
}
