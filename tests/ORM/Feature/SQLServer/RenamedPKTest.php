<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Feature\SQLServer;

// phpcs:ignore
use Cycle\ORM\Tests\Feature\Common\RenamedPKTest as CommonClass;

#[\Testo\Filter\Group('driver', 'driver-sqlserver')]
class RenamedPKTest extends CommonClass
{
    public const DRIVER = 'sqlserver';

    #[\Testo\Test]
    #[\Testo\Skip('Cannot insert explicit value for identity column in table \'simple_entity\' when IDENTITY_INSERT is set to OFF.')]
    public function testCreateWithPredefinedId(): void
    {
    }
}
