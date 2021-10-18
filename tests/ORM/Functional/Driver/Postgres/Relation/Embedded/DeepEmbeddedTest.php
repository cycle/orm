<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Functional\Driver\Postgres\Relation\Embedded;

/**
 * @group driver
 * @group driver-postgres
 */
class DeepEmbeddedTest extends \Cycle\ORM\Tests\Functional\Relation\Embedded\DeepEmbeddedTest
{
    public const DRIVER = 'postgres';
}
