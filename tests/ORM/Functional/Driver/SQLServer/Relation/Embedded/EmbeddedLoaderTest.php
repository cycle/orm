<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Functional\Driver\SQLServer\Relation\Embedded;

// phpcs:ignore
use Cycle\ORM\Tests\Functional\Driver\Common\Relation\Embedded\EmbeddedLoaderTest as CommonClass;

/**
 * @group driver
 * @group driver-sqlserver
 */
class EmbeddedLoaderTest extends CommonClass
{
    public const DRIVER = 'sqlserver';
}
