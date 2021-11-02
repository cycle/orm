<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Functional\Driver\Postgres\Relation\Embedded;

// phpcs:ignore
use Cycle\ORM\Tests\Functional\Driver\Common\Relation\Embedded\DeepEmbeddedTest as CommonClass;

/**
 * @group driver
 * @group driver-postgres
 */
class DeepEmbeddedTest extends CommonClass
{
    public const DRIVER = 'postgres';
}
