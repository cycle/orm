<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Functional\Driver\SQLServer\Relation\HasOne;

/**
 * @group driver
 * @group driver-sqlserver
 */
class HasOneCompositeKeyTest extends \Cycle\ORM\Tests\Functional\Relation\HasOne\HasOneCompositeKeyTest
{
    public const DRIVER = 'sqlserver';
}
