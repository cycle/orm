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

final class OrmTest
{
    #[\Testo\Test]
    public function testPrepareServicesTwice(): void
    {
        $orm = $this->createOrm();
        $orm->prepareServices();
        $orm->prepareServices();
        \Testo\Assert::true(true, 'There aren\'t any errors.');
    }

    #[\Testo\Test]
    public function testORMClone(): void
    {
        $orm = $this->createOrm();
        $new = $orm->withFactory($orm->getFactory());
        \Testo\Assert::notSame($new, $orm);
    }

    #[\Testo\Test]
    public function testORMCloneWithSchema(): void
    {
        $orm = $this->createOrm();
        $new = $orm->with(new Schema([]));

        \Testo\Assert::notSame($orm, $new);
        \Testo\Assert::notSame($orm->getSchema(), $new->getSchema());
    }

    #[\Testo\Test]
    public function testORMCloneWithFactory(): void
    {
        $orm = $this->createOrm();
        $new = $orm->with(factory: new Factory(new DatabaseManager(new DatabaseConfig([]))));

        \Testo\Assert::notSame($new, $orm);
        \Testo\Assert::notSame($orm->getFactory(), $new->getFactory());
    }

    #[\Testo\Test]
    public function testORMCloneWithHeap(): void
    {
        $orm = $this->createOrm();
        $new = $orm->with(heap: new Heap());

        \Testo\Assert::notSame($orm, $new);
        \Testo\Assert::notSame($orm->getHeap(), $new->getHeap());
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
