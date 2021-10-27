<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Functional\Driver\SQLite\Relation\Embedded;

// phpcs:ignore
use Cycle\ORM\Tests\Functional\Driver\Common\Relation\Embedded\DeepEmbeddedTest as CommonClass;

/**
 * @group driver
 * @group driver-sqlite
 */
class DeepEmbeddedTest extends CommonClass
{
    public const DRIVER = 'sqlite';
}
