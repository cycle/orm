<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Feature\SQLServer\Typecast;

// phpcs:ignore
use Cycle\ORM\Tests\Feature\Common\Typecast\TypecastTest as CommonClass;

/**
 * @group driver
 * @group driver-sqlserver
 */
class TypecastTest extends CommonClass
{
    public const DRIVER = 'sqlserver';

    public function testOrmMakePreparedDataCastFlag(): void
    {
        // phpcs:ignore
        $this->markTestSkipped("Cannot insert explicit value for identity column in table 'simple_entity' when IDENTITY_INSERT is set to OFF.");
    }

    public function testOrmMakeRawDataCastFlag(): void
    {
        // phpcs:ignore
        $this->markTestSkipped("Cannot insert explicit value for identity column in table 'simple_entity' when IDENTITY_INSERT is set to OFF.");
    }

    public function testOrmMakeRehydrateRawDataCastFlag(): void
    {
        // phpcs:ignore
        $this->markTestSkipped("Cannot insert explicit value for identity column in table 'simple_entity' when IDENTITY_INSERT is set to OFF.");
    }
}
