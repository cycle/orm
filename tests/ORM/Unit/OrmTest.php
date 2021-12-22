<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Unit;

use Cycle\Database\Config\DatabaseConfig;
use Cycle\Database\DatabaseManager;
use Cycle\ORM\Config\RelationConfig;
use Cycle\ORM\Factory;
use Cycle\ORM\Heap\Heap;
use Cycle\ORM\ORM;
use Cycle\ORM\Schema;
use Cycle\ORM\Tests\Util\SimpleFactory;
use PHPUnit\Framework\TestCase;

final class OrmTest extends TestCase
{
    public function testPrepareServicesTwice(): void
    {
        $orm = $this->createOrm();
        $orm->prepareServices();
        $orm->prepareServices();
        $this->assertTrue(true, 'There aren\'t any errors.');
    }

    public function testORMClone(): void
    {
        $orm = $this->createOrm();
        $new = $orm->withFactory($orm->getFactory());
        $this->assertNotSame($orm, $new);
    }

    public function testORMCloneWithSchema(): void
    {
        $orm = $this->createOrm();
        $new = $orm->with(new Schema([]));

        $this->assertNotSame($new, $orm);
        $this->assertNotSame($new->getSchema(), $orm->getSchema());
    }

    public function testORMCloneWithFactory(): void
    {
        $orm = $this->createOrm();
        $new = $orm->with(factory: new Factory(new DatabaseManager(new DatabaseConfig([])),));

        $this->assertNotSame($orm, $new);
        $this->assertNotSame($new->getFactory(), $orm->getFactory());
    }

    public function testORMCloneWithHeap(): void
    {
        $orm = $this->createOrm();
        $new = $orm->with(heap: new Heap());

        $this->assertNotSame($new, $orm);
        $this->assertNotSame($new->getHeap(), $orm->getHeap());
    }

    private function createOrm(): ORM
    {
        return new ORM(
            factory: new Factory(
                new DatabaseManager(new DatabaseConfig([])),
                RelationConfig::getDefault(),
                new SimpleFactory()
            ),
            schema: new Schema([])
        );
    }
}
