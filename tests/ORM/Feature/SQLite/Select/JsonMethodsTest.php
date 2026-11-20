<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Feature\SQLite\Select;

// phpcs:ignore
use Cycle\Database\Exception\DriverException;
use Cycle\ORM\Tests\Feature\Common\Select\JsonMethodsTest as CommonClass;

#[\Testo\Filter\Group('driver', 'driver-sqlite')]
class JsonMethodsTest extends CommonClass
{
    public const DRIVER = 'sqlite';

    #[\Testo\Test]
    public function testWhereJsonContains(): void
    {
        \Testo\Expect::exception(DriverException::class);
        parent::testWhereJsonContains();
    }

    #[\Testo\Test]
    public function testWhereJsonContainsWithRelation(): void
    {
        \Testo\Expect::exception(DriverException::class);
        parent::testWhereJsonContainsWithRelation();
    }

    #[\Testo\Test]
    public function testOrWhereJsonContains(): void
    {
        \Testo\Expect::exception(DriverException::class);
        parent::testOrWhereJsonContains();
    }

    #[\Testo\Test]
    public function testOrWhereJsonContainsWithRelation(): void
    {
        \Testo\Expect::exception(DriverException::class);
        parent::testOrWhereJsonContainsWithRelation();
    }

    #[\Testo\Test]
    public function testWhereJsonDoesntContain(): void
    {
        \Testo\Expect::exception(DriverException::class);
        parent::testWhereJsonDoesntContain();
    }

    #[\Testo\Test]
    public function testWhereJsonDoesntContainWithRelation(): void
    {
        \Testo\Expect::exception(DriverException::class);
        parent::testWhereJsonDoesntContainWithRelation();
    }

    #[\Testo\Test]
    public function testOrWhereJsonDoesntContain(): void
    {
        \Testo\Expect::exception(DriverException::class);
        parent::testOrWhereJsonDoesntContain();
    }

    #[\Testo\Test]
    public function testOrWhereJsonDoesntContainWithRelation(): void
    {
        \Testo\Expect::exception(DriverException::class);
        parent::testOrWhereJsonDoesntContainWithRelation();
    }
}
