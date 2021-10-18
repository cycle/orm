<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Functional\Driver\Postgres\Relation\HasOne;

/**
 * @group driver
 * @group driver-postgres
 */
class HasOneProxyMapperTest extends \Cycle\ORM\Tests\Functional\Relation\HasOne\HasOneProxyMapperTest
{
    public const DRIVER = 'postgres';
}
