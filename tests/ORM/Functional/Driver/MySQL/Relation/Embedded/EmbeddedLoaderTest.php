<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Functional\Driver\MySQL\Relation\Embedded;

// phpcs:ignore
use Cycle\ORM\Tests\Functional\Driver\Common\Relation\Embedded\EmbeddedLoaderTest as CommonClass;

/**
 * @group driver
 * @group driver-mysql
 */
class EmbeddedLoaderTest extends CommonClass
{
    public const DRIVER = 'mysql';
}
