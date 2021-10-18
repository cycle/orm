<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Functional\Driver\SQLServer\Relation\ManyToMany;

/**
 * @group driver
 * @group driver-sqlserver
 */
class ManyToManyCompositeKeyTest extends \Cycle\ORM\Tests\Functional\Relation\ManyToMany\ManyToManyCompositeKeyTest
{
    public const DRIVER = 'sqlserver';
}
