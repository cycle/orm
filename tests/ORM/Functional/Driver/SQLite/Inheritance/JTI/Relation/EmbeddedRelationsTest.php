<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Functional\Driver\SQLite\Inheritance\JTI\Relation;

// phpcs:ignore
use Cycle\ORM\Tests\Functional\Driver\Common\Inheritance\JTI\Relation\EmbeddedRelationsTest as CommonClass;

/**
 * @group driver
 * @group driver-sqlite
 */
class EmbeddedRelationsTest extends CommonClass
{
    public const DRIVER = 'sqlite';
}
