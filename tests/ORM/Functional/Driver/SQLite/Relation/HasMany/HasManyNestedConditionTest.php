<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Functional\Driver\SQLite\Relation\HasMany;

// phpcs:ignore
use Cycle\ORM\Tests\Functional\Driver\Common\Relation\HasMany\HasManyNestedConditionTest as CommonClass;

/**
 * @group driver
 * @group driver-sqlite
 */
class HasManyNestedConditionTest extends CommonClass
{
    public const DRIVER = 'sqlite';
}
