<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Unit\Collection;

use Cycle\ORM\Collection\CollectionFactoryInterface;
use Cycle\ORM\Collection\IlluminateCollectionFactory;
use Illuminate\Support\Collection;

class IlluminateCollectionFactoryTest extends BaseTest
{
    public function testGetInterface(): void
    {
        $this->assertSame(Collection::class, $this->getFactory()->getInterface());
    }

    /**
     * @dataProvider collectionDataProvider
     */
    public function testCollectShouldReturnArray($data): void
    {
        $collection = $this->getFactory()->collect($data);

        $this->assertInstanceOf(Collection::class, $collection);
        $this->assertSame([
            'foo' => 'bar',
            'baz' => 'bar',
        ], $collection->toArray());
    }

    /**
     * @dataProvider collectionDataProvider
     */
    public function testCollectShouldReturnArrayForCustomCollection($data): void
    {
        $collection = $this->getFactory()
            ->withCollectionClass(CustomCollection::class)
            ->collect($data);

        $this->assertInstanceOf(CustomCollection::class, $collection);
        $this->assertSame([
            'foo' => 'bar',
            'baz' => 'bar',
        ], $collection->toArray());
    }

    protected function getFactory(): CollectionFactoryInterface
    {
        return new IlluminateCollectionFactory();
    }
}

class CustomCollection extends Collection
{
}
