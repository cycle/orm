<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Functional\Driver\SQLServer\Driver\Common\Relation\Embedded;

// phpcs:ignore
use Cycle\ORM\Tests\Functional\Driver\Common\Relation\Embedded\DeepEmbeddedTest as CommonTest;

/**
 * @group driver
 * @group driver-sqlserver
 */
class DeepEmbeddedTest extends CommonTest
{
    public const DRIVER = 'sqlserver';
}
