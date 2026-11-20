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
use Testo\Assert;
use Testo\Test;

#[Test]
final class OrmTest
{
    public function testPrepareServicesTwice(): void
    {
        $orm = $this->createOrm();
        $orm->prepareServices();
        $orm->prepareServices();
        Assert::true(true, 'There aren\'t any errors.');
    }

    public function testORMClone(): void
    {
        $orm = $this->createOrm();
        $new = $orm->withFactory($orm->getFactory());
        Assert::notSame($new, $orm);
    }

    public function testORMCloneWithSchema(): void
    {
        $orm = $this->createOrm();
        $new = $orm->with(new Schema([]));

        Assert::notSame($orm, $new);
        Assert::notSame($orm->getSchema(), $new->getSchema());
    }

    public function testORMCloneWithFactory(): void
    {
        $orm = $this->createOrm();
        $new = $orm->with(factory: new Factory(new DatabaseManager(new DatabaseConfig([]))));

        Assert::notSame($new, $orm);
        Assert::notSame($orm->getFactory(), $new->getFactory());
    }

    public function testORMCloneWithHeap(): void
    {
        $orm = $this->createOrm();
        $new = $orm->with(heap: new Heap());

        Assert::notSame($orm, $new);
        Assert::notSame($orm->getHeap(), $new->getHeap());
    }

    private function createOrm(): ORM
    {
        return new ORM(
            factory: new Factory(
                new DatabaseManager(new DatabaseConfig([])),
                RelationConfig::getDefault(),
                new SimpleFactory(),
            ),
            schema: new Schema([]),
        );
    }
}
