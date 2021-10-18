<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Functional\Driver\SQLite\Driver\Common\Relation\Embedded;

// phpcs:ignore
use Cycle\ORM\Tests\Functional\Driver\Common\Relation\Embedded\EmbeddedCompositeKeyTest as CommonTest; 
 
/**
 * @group driver
 * @group driver-sqlite
 */
class EmbeddedCompositeKeyTest extends CommonTest
{
    public const DRIVER = 'sqlite';
}
