<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Unit\Service;

use Cycle\ORM\Heap\Node;
use Cycle\ORM\Mapper\Mapper;
use Cycle\ORM\ORMInterface;
use Cycle\ORM\Schema;
use Cycle\ORM\Tests\Fixtures\CompositePK;
use Cycle\Testing\ORM\Memory;
use Testo\Assert;
use Testo\Test;

#[Test]
final class EntityFactoryTest
{
    public function loadedEntityIsTakenFromHeapByCompositeKey(): void
    {
        $orm = $this->orm();
        $entity = $orm->make('composite', ['key1' => 1, 'key2' => 2, 'key3' => 3], Node::MANAGED);

        Assert::same($orm->make('composite', ['key1' => 1, 'key2' => 2], Node::MANAGED), $entity);
    }

    public function partialCompositeKeyMakesNewEntity(): void
    {
        $orm = $this->orm();
        $entity = $orm->make('composite', ['key1' => 1, 'key2' => 2, 'key3' => 3], Node::MANAGED);

        $other = $orm->make('composite', ['key1' => 1, 'key3' => 4], Node::MANAGED);

        Assert::notSame($other, $entity);
        Assert::same($other->key3, 4);
        Assert::null($other->key2);
    }

    private function orm(): ORMInterface
    {
        return Memory::orm(new Schema([
            'composite' => [
                Schema::ENTITY => CompositePK::class,
                Schema::MAPPER => Mapper::class,
                Schema::DATABASE => 'default',
                Schema::TABLE => 'composite',
                Schema::PRIMARY_KEY => ['key1', 'key2'],
                Schema::COLUMNS => ['key1', 'key2', 'key3'],
                Schema::SCHEMA => [],
                Schema::RELATIONS => [],
            ],
        ]));
    }
}
