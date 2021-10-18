<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Functional\Driver\SQLite\Relation\HasOne;

/**
 * @group driver
 * @group driver-sqlite
 */
class HasOneCompositeKeyTest extends \Cycle\ORM\Tests\Functional\Relation\HasOne\HasOneCompositeKeyTest
{
    public const DRIVER = 'sqlite';
}
