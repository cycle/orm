<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Functional\Driver\MySQL\Driver\Common\Relation\Embedded;

// phpcs:ignore
use Cycle\ORM\Tests\Functional\Driver\Common\Relation\Embedded\EmbeddedLoaderTest as CommonTest;

/**
 * @group driver
 * @group driver-mysql
 */
class EmbeddedLoaderTest extends CommonTest
{
    public const DRIVER = 'mysql';
}
