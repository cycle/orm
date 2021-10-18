<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Functional\Driver\SQLServer\Relation\RefersTo;

/**
 * @group driver
 * @group driver-sqlserver
 */
class RefersToRelationCompositeKeyTest extends \Cycle\ORM\Tests\Functional\Relation\RefersTo\RefersToRelationCompositeKeyTest
{
    public const DRIVER = 'sqlserver';
}
