<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Unit\Collection;

use Cycle\ORM\Collection\CollectionFactoryInterface;
use Cycle\ORM\Collection\LoophpCollectionFactory;
use Doctrine\Common\Collections\ArrayCollection as DoctrineCollection;
use loophp\collection\Collection;
use loophp\collection\Contract\Collection as CollectionInterface;

class LoophpCollectionFactoryTest extends BaseTest
{
    public function testGetInterface(): void
    {
        $this->assertSame(CollectionInterface::class, $this->getFactory()->getInterface());
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
        ], $collection->all(false));
    }

    public function testWitherCollectShouldReturnArray(): void
    {
        $collection = $this
            ->getFactory()
            ->withCollectionClass(DoctrineCollection::class);

        $this->assertInstanceOf(DoctrineCollection::class, $collection->collect([]));

        $collection = $collection
            ->withCollectionClass(Collection::class);

        $this->assertInstanceOf(Collection::class, $collection->collect([]));
    }

    protected function getFactory(): CollectionFactoryInterface
    {
        return new LoophpCollectionFactory();
    }
}
