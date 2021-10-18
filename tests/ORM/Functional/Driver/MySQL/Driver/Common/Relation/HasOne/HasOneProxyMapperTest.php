<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Functional\Driver\MySQL\Driver\Common\Relation\HasOne;

// phpcs:ignore
use Cycle\ORM\Tests\Functional\Driver\Common\Relation\HasOne\HasOneProxyMapperTest as CommonTest;

/**
 * @group driver
 * @group driver-mysql
 */
class HasOneProxyMapperTest extends CommonTest
{
    public const DRIVER = 'mysql';
}
