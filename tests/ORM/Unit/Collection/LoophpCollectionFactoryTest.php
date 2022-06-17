<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Unit\Collection;

use Cycle\ORM\Collection\CollectionFactoryInterface;
use Cycle\ORM\Collection\LoophpCollectionFactory;
use Cycle\ORM\Exception\CollectionFactoryException;
use IteratorIterator;
use loophp\collection\Collection;

class LoophpCollectionFactoryTest extends BaseTest
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
        ], $collection->all(false));
    }

    public function testWithCollectionClassImmutability(): void
    {
        $factory = $this->getFactory();

        $newFactory = $factory->withCollectionClass(Collection::class);

        $this->assertNotSame($factory, $newFactory);
    }

    public function testWithCollectionClassNotCollection(): void
    {
        $this->expectException(CollectionFactoryException::class);
        $this->expectExceptionMessage('Unsupported collection class `IteratorIterator`.');

        // todo use mock of CollectionInterface instead
        $this->getFactory()->withCollectionClass(IteratorIterator::class);
    }

    protected function getFactory(): CollectionFactoryInterface
    {
        return new LoophpCollectionFactory();
    }
}
