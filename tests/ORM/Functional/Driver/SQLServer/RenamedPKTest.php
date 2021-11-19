<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Functional\Driver\SQLServer;

// phpcs:ignore
use Cycle\ORM\Tests\Functional\Driver\Common\RenamedPKTest as CommonClass;

/**
 * @group driver
 * @group driver-sqlserver
 */
class RenamedPKTest extends CommonClass
{
    public const DRIVER = 'sqlserver';

    public function testCreateWithPredefinedId(): void
    {
        // phpcs:ignore
        $this->markTestSkipped("Cannot insert explicit value for identity column in table 'simple_entity' when IDENTITY_INSERT is set to OFF.");
    }
}
