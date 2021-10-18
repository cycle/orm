<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Functional\Driver\SQLServer\Driver\Common\Relation\BelongsTo;

// phpcs:ignore
use Cycle\ORM\Tests\Functional\Driver\Common\Relation\BelongsTo\BelongsToCompositeKeyTest as CommonTest; 
 
/**
 * @group driver
 * @group driver-sqlserver
 */
class BelongsToCompositeKeyTest extends CommonTest
{
    public const DRIVER = 'sqlserver';
}
