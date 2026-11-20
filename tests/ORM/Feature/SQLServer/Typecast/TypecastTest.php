<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Feature\SQLServer\Typecast;

// phpcs:ignore
use Cycle\ORM\Tests\Feature\Common\Typecast\TypecastTest as CommonClass;

#[\Testo\Filter\Group('driver', 'driver-sqlserver')]
class TypecastTest extends CommonClass
{
    public const DRIVER = 'sqlserver';

    #[\Testo\Test]
    #[\Testo\Skip('Cannot insert explicit value for identity column in table \'simple_entity\' when IDENTITY_INSERT is set to OFF.')]
    public function testOrmMakePreparedDataCastFlag(): void
    {
    }

    #[\Testo\Test]
    #[\Testo\Skip('Cannot insert explicit value for identity column in table \'simple_entity\' when IDENTITY_INSERT is set to OFF.')]
    public function testOrmMakeRawDataCastFlag(): void
    {
    }

    #[\Testo\Test]
    #[\Testo\Skip('Cannot insert explicit value for identity column in table \'simple_entity\' when IDENTITY_INSERT is set to OFF.')]
    public function testOrmMakeRehydrateRawDataCastFlag(): void
    {
    }
}
