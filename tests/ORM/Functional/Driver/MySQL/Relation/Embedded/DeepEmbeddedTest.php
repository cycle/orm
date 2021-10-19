<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Functional\Driver\MySQL\Relation\Embedded;

// phpcs:ignore
use Cycle\ORM\Tests\Functional\Driver\Common\Relation\Embedded\DeepEmbeddedTest as CommonTest;

/**
 * @group driver
 * @group driver-mysql
 */
class DeepEmbeddedTest extends CommonTest
{
    public const DRIVER = 'mysql';
}
