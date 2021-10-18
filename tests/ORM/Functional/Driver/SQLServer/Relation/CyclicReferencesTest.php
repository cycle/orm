<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Functional\Driver\SQLServer\Relation;

/**
 * @group driver
 * @group driver-sqlserver
 */
class CyclicReferencesTest extends \Cycle\ORM\Tests\Functional\Relation\CyclicReferencesTest
{
    public const DRIVER = 'sqlserver';
}
