<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Unit\Collection;

use Cycle\ORM\Collection\CollectionFactoryInterface;
use Cycle\ORM\Collection\IlluminateCollectionFactory;
use Illuminate\Support\Collection;

class IlluminateCollectionFactoryTest extends BaseTest
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

        \Testo\Assert::instanceOf($collection, Collection::class);
        \Testo\Assert::same($collection->toArray(), [
            'foo' => 'bar',
            'baz' => 'bar',
        ]);
    }

    /**
     * @param mixed $data
     */
    #[\Testo\Test]
    #[\Testo\Data\DataProvider('collectionDataProvider')]
    public function testCollectShouldReturnArrayForCustomCollection($data): void
    {
        $collection = $this->getFactory()
            ->withCollectionClass(CustomCollection::class)
            ->collect($data);

        \Testo\Assert::instanceOf($collection, CustomCollection::class);
        \Testo\Assert::same($collection->toArray(), [
            'foo' => 'bar',
            'baz' => 'bar',
        ]);
    }

    protected function getFactory(): CollectionFactoryInterface
    {
        return new IlluminateCollectionFactory();
    }
}

class CustomCollection extends Collection {}
