<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Functional\Driver\MySQL\Relation\HasMany;

// phpcs:ignore
use Cycle\ORM\Tests\Functional\Driver\Common\Relation\HasMany\HasManyProxyMapperTest as CommonClass;

/**
 * @group driver
 * @group driver-mysql
 */
class HasManyProxyMapperTest extends CommonClass
{
    public const DRIVER = 'mysql';
}
