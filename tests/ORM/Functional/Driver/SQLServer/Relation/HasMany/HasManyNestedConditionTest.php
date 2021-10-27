<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Functional\Driver\SQLServer\Relation\HasMany;

// phpcs:ignore
use Cycle\ORM\Tests\Functional\Driver\Common\Relation\HasMany\HasManyNestedConditionTest as CommonClass;

/**
 * @group driver
 * @group driver-sqlserver
 */
class HasManyNestedConditionTest extends CommonClass
{
    public const DRIVER = 'sqlserver';
}
