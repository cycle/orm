<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Functional\Driver\MySQL\Driver\Common\Relation\Embedded;

// phpcs:ignore
use Cycle\ORM\Tests\Functional\Driver\Common\Relation\Embedded\EmbeddedRelationTest as CommonTest;

/**
 * @group driver
 * @group driver-mysql
 */
class EmbeddedRelationTest extends CommonTest
{
    public const DRIVER = 'mysql';
}
