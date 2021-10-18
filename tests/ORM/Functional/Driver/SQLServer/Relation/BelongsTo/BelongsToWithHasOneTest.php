<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Functional\Driver\SQLServer\Relation\BelongsTo;

/**
 * @group driver
 * @group driver-sqlserver
 */
class BelongsToWithHasOneTest extends \Cycle\ORM\Tests\Functional\Relation\BelongsTo\BelongsToWithHasOneTest
{
    public const DRIVER = 'sqlserver';
}
