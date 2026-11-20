<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Unit\Collection;

use Cycle\ORM\Collection\CollectionFactoryInterface;
use Cycle\ORM\Collection\IlluminateCollectionFactory;
use Illuminate\Support\Collection;
use Testo\Assert;
use Testo\Data\DataProvider;
use Testo\Test;

final class IlluminateCollectionFactoryTest extends BaseTest
{
    #[Test]
    public function testGetInterface(): void
    {
        Assert::same($this->getFactory()->getInterface(), Collection::class);
    }

    /**
     * @param mixed $data
     */
    #[Test]
    #[DataProvider('collectionDataProvider')]
    public function testCollectShouldReturnArray($data): void
    {
        $collection = $this->getFactory()->collect($data);

        Assert::instanceOf($collection, Collection::class);
        Assert::same($collection->toArray(), [
            'foo' => 'bar',
            'baz' => 'bar',
        ]);
    }

    /**
     * @param mixed $data
     */
    #[Test]
    #[DataProvider('collectionDataProvider')]
    public function testCollectShouldReturnArrayForCustomCollection($data): void
    {
        $collection = $this->getFactory()
            ->withCollectionClass(CustomCollection::class)
            ->collect($data);

        Assert::instanceOf($collection, CustomCollection::class);
        Assert::same($collection->toArray(), [
            'foo' => 'bar',
            'baz' => 'bar',
        ]);
    }

    #[Test]
    public function withCollectionClassKeepsOriginalFactory(): void
    {
        $factory = $this->getFactory();

        $factory->withCollectionClass(CustomCollection::class);

        Assert::same($factory->collect(['foo' => 'bar'])::class, Collection::class);
    }

    protected function getFactory(): CollectionFactoryInterface
    {
        return new IlluminateCollectionFactory();
    }
}

class CustomCollection extends Collection {}
