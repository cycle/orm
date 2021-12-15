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

        $this->assertInstanceOf(ArrayCollection::class, $collection);
        $this->assertSame([
            'foo' => 'bar',
            'baz' => 'bar',
        ], $collection->toArray());
    }

    public function testCollectPivotStorageWithArrayCollection()
    {
        $collection = $this->getFactory()->collect(
            new PivotedStorage($array = [
                'foo' => 'bar',
                'baz' => 'bar',
            ])
        );

        $this->assertInstanceOf(PivotedCollection::class, $collection);
        $this->assertSame($array, $collection->toArray());
    }

    public function testCollectPivotStorageWithPivotedCollection()
    {
        $collection = $this->getFactory()
            ->withCollectionClass(CustomPivotedCollection::class)
            ->collect(
                new PivotedStorage($array = [
                    'foo' => 'bar',
                    'baz' => 'bar',
                ])
            );

        $this->assertInstanceOf(CustomPivotedCollection::class, $collection);
        $this->assertSame($array, $collection->toArray());
    }

    protected function getFactory(): CollectionFactoryInterface
    {
        return new DoctrineCollectionFactory();
    }
}

class CustomPivotedCollection extends PivotedCollection
{

}
