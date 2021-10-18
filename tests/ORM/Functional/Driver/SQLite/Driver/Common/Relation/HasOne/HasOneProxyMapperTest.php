<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Functional\Driver\SQLite\Driver\Common\Relation\HasOne;

// phpcs:ignore
use Cycle\ORM\Tests\Functional\Driver\Common\Relation\HasOne\HasOneProxyMapperTest as CommonTest; 
 
/**
 * @group driver
 * @group driver-sqlite
 */
class HasOneProxyMapperTest extends CommonTest
{
    public const DRIVER = 'sqlite';
}
