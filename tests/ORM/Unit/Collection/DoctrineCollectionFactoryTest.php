<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Unit\Collection;

use Cycle\ORM\Collection\CollectionFactoryInterface;
use Cycle\ORM\Collection\DoctrineCollectionFactory;
use Cycle\ORM\Collection\Pivoted\PivotedCollection;
use Cycle\ORM\Collection\Pivoted\PivotedStorage;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

class DoctrineCollectionFactoryTest extends BaseTest
{
    #[\Testo\Test]
    public function testGetInterface(): void
    {
        \Testo\Assert::same($this->getFactory()->getInterface(), Collection::class);
    }

    /**
     * @param mixed $data
     */
    #[\Testo\Test]
    #[\Testo\Data\DataProvider('collectionDataProvider')]
    public function testCollectShouldReturnArray($data): void
    {
        $collection = $this->getFactory()->collect($data);

        \Testo\Assert::instanceOf($collection, ArrayCollection::class);
        \Testo\Assert::same($collection->toArray(), [
            'foo' => 'bar',
            'baz' => 'bar',
        ]);
    }

    #[\Testo\Test]
    public function testCollectPivotStorageWithArrayCollection(): void
    {
        $collection = $this->getFactory()->collect(
            new PivotedStorage($array = [
                'foo' => 'bar',
                'baz' => 'bar',
            ]),
        );

        \Testo\Assert::instanceOf($collection, PivotedCollection::class);
        \Testo\Assert::same($collection->toArray(), $array);
    }

    #[\Testo\Test]
    public function testCollectPivotStorageWithPivotedCollection(): void
    {
        $collection = $this->getFactory()
            ->withCollectionClass(CustomPivotedCollection::class)
            ->collect(
                new PivotedStorage($array = [
                    'foo' => 'bar',
                    'baz' => 'bar',
                ]),
            );

        \Testo\Assert::instanceOf($collection, CustomPivotedCollection::class);
        \Testo\Assert::same($collection->toArray(), $array);
    }

    protected function getFactory(): CollectionFactoryInterface
    {
        return new DoctrineCollectionFactory();
    }
}

class CustomPivotedCollection extends PivotedCollection {}
