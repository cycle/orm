<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Functional\Driver\MySQL\Relation\HasMany;

// phpcs:ignore
use Cycle\ORM\Tests\Functional\Driver\Common\Relation\HasMany\HasManyCompositeKeyTest as CommonClass;

/**
 * @group driver
 * @group driver-mysql
 */
class HasManyCompositeKeyTest extends CommonClass
{
    public const DRIVER = 'mysql';
}
