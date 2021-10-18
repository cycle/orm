<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Functional\Driver\SQLite\Relation;

/**
 * @group driver
 * @group driver-sqlite
 */
class CyclicReferencesTest extends \Cycle\ORM\Tests\Functional\Relation\CyclicReferencesTest
{
    public const DRIVER = 'sqlite';
}
