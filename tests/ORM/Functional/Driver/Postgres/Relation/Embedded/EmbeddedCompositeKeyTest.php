<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Functional\Driver\Postgres\Relation\Embedded;

// phpcs:ignore
use Cycle\ORM\Tests\Functional\Driver\Common\Relation\Embedded\EmbeddedCompositeKeyTest as CommonClass;

/**
 * @group driver
 * @group driver-postgres
 */
class EmbeddedCompositeKeyTest extends CommonClass
{
    public const DRIVER = 'postgres';
}
