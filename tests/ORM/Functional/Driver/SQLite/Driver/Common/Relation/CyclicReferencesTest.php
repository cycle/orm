<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Functional\Driver\SQLite\Driver\Common\Relation;

/**
 * @group driver
 * @group driver-sqlite
 */
class CyclicReferencesTest extends \Cycle\ORM\Tests\Functional\Driver\Common\Relation\CyclicReferencesTest
{
    public const DRIVER = 'sqlite';
}
