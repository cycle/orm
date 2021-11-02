<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Functional\Driver\SQLServer\Relation;

// phpcs:ignore
use Cycle\ORM\Tests\Functional\Driver\Common\Relation\CyclicReferencesTest as CommonClass;

/**
 * @group driver
 * @group driver-sqlserver
 */
class CyclicReferencesTest extends CommonClass
{
    public const DRIVER = 'sqlserver';
}
