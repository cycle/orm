<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Unit\Collection;

use Cycle\ORM\Collection\CollectionFactoryInterface;
use Cycle\ORM\Collection\DoctrineCollectionFactory;
use Cycle\ORM\Collection\Pivoted\PivotedCollection;
use Cycle\ORM\Collection\Pivoted\PivotedStorage;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Testo\Assert;
use Testo\Data\DataProvider;
use Testo\Test;

final class DoctrineCollectionFactoryTest extends BaseTest
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

        Assert::instanceOf($collection, ArrayCollection::class);
        Assert::same($collection->toArray(), [
            'foo' => 'bar',
            'baz' => 'bar',
        ]);
    }

    #[Test]
    public function testCollectPivotStorageWithArrayCollection(): void
    {
        $collection = $this->getFactory()->collect(
            new PivotedStorage($array = [
                'foo' => 'bar',
                'baz' => 'bar',
            ]),
        );

        Assert::instanceOf($collection, PivotedCollection::class);
        Assert::same($collection->toArray(), $array);
    }

    #[Test]
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

        Assert::instanceOf($collection, CustomPivotedCollection::class);
        Assert::same($collection->toArray(), $array);
    }

    #[Test]
    public function withCollectionClassKeepsOriginalFactory(): void
    {
        $factory = $this->getFactory();

        $factory->withCollectionClass(CustomPivotedCollection::class);

        Assert::same($factory->collect(['foo' => 'bar'])::class, ArrayCollection::class);
    }

    protected function getFactory(): CollectionFactoryInterface
    {
        return new DoctrineCollectionFactory();
    }
}

class CustomPivotedCollection extends PivotedCollection {}
