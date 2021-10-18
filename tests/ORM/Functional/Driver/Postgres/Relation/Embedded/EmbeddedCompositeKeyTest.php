<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Functional\Driver\Postgres\Relation\Embedded;

/**
 * @group driver
 * @group driver-postgres
 */
class EmbeddedCompositeKeyTest extends \Cycle\ORM\Tests\Functional\Relation\Embedded\EmbeddedCompositeKeyTest
{
    public const DRIVER = 'postgres';
}
