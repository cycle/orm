<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Functional\Driver\SQLite\Relation\HasOne;

/**
 * @group driver
 * @group driver-sqlite
 */
class HasOneProxyMapperTest extends \Cycle\ORM\Tests\Functional\Relation\HasOne\HasOneProxyMapperTest
{
    public const DRIVER = 'sqlite';
}
