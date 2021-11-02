<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Functional\Driver\MySQL\Relation\HasOne;

// phpcs:ignore
use Cycle\ORM\Tests\Functional\Driver\Common\Relation\HasOne\HasOneProxyMapperTest as CommonClass;

/**
 * @group driver
 * @group driver-mysql
 */
class HasOneProxyMapperTest extends CommonClass
{
    public const DRIVER = 'mysql';
}
