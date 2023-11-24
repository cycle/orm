<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Functional\Driver\SQLite\Select;

// phpcs:ignore
use Cycle\Database\Exception\DriverException;
use Cycle\ORM\Tests\Functional\Driver\Common\Select\JsonMethodsTest as CommonClass;

/**
 * @group driver
 * @group driver-sqlite
 */
class JsonMethodsTest extends CommonClass
{
    public const DRIVER = 'sqlite';

    public function testWhereJsonContains(): void
    {
        $this->expectException(DriverException::class);
        parent::testWhereJsonContains();
    }

    public function testWhereJsonContainsWithRelation(): void
    {
        $this->expectException(DriverException::class);
        parent::testWhereJsonContainsWithRelation();
    }

    public function testOrWhereJsonContains(): void
    {
        $this->expectException(DriverException::class);
        parent::testOrWhereJsonContains();
    }

    public function testOrWhereJsonContainsWithRelation(): void
    {
        $this->expectException(DriverException::class);
        parent::testOrWhereJsonContainsWithRelation();
    }

    public function testWhereJsonDoesntContain(): void
    {
        $this->expectException(DriverException::class);
        parent::testWhereJsonDoesntContain();
    }

    public function testWhereJsonDoesntContainWithRelation(): void
    {
        $this->expectException(DriverException::class);
        parent::testWhereJsonDoesntContainWithRelation();
    }

    public function testOrWhereJsonDoesntContain(): void
    {
        $this->expectException(DriverException::class);
        parent::testOrWhereJsonDoesntContain();
    }

    public function testOrWhereJsonDoesntContainWithRelation(): void
    {
        $this->expectException(DriverException::class);
        parent::testOrWhereJsonDoesntContainWithRelation();
    }
}
