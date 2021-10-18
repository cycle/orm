<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Functional\Driver\SQLite;

/**
 * @group driver
 * @group driver-sqlite
 */
class RenamedPKTest extends \Cycle\ORM\Tests\Functional\RenamedPKTest
{
    public const DRIVER = 'sqlite';
}
