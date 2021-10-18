<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Functional\Driver\SQLite\Relation\Embedded;

/**
 * @group driver
 * @group driver-sqlite
 */
class EmbeddedCompositeKeyTest extends \Cycle\ORM\Tests\Functional\Relation\Embedded\EmbeddedCompositeKeyTest
{
    public const DRIVER = 'sqlite';
}
