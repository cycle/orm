<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Functional\Driver\SQLServer\Relation\HasMany;

/**
 * @group driver
 * @group driver-sqlserver
 */
class HasManyCompositeKeyTest extends \Cycle\ORM\Tests\Functional\Relation\HasMany\HasManyCompositeKeyTest
{
    public const DRIVER = 'sqlserver';
}
