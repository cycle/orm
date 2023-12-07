<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Unit\Collection;

use Cycle\ORM\Collection\CollectionFactoryInterface;
use Cycle\ORM\Collection\LoophpCollectionFactory;
use Cycle\ORM\Exception\CollectionFactoryException;
use IteratorIterator;
use loophp\collection\CollectionDecorator;
use loophp\collection\Contract\Collection as CollectionInterface;
use loophp\collection\Collection;

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

        $this->getFactory()->withCollectionClass(IteratorIterator::class);
    }

    /**
     * @dataProvider collectionDataProvider
     */
    public function testWithCollectionClassInterface(mixed $data): void
    {
        $collection = $this->getFactory()->withCollectionClass(CollectionInterface::class)->collect($data);

        $this->assertInstanceOf(Collection::class, $collection);
        $this->assertSame(['foo' => 'bar', 'baz' => 'bar'], $collection->all(false));
    }

    /**
     * @dataProvider collectionDataProvider
     */
    public function testWithCollectionClassCustomClass(mixed $data): void
    {
        $customClass = new class (Collection::empty()) extends CollectionDecorator {};

        $collection = $this->getFactory()->withCollectionClass($customClass::class)->collect($data);

        $this->assertInstanceOf($collection::class, $collection);
        $this->assertSame(['foo' => 'bar', 'baz' => 'bar'], $collection->all(false));
    }

    public function testWithCollectionClassNotClass(): void
    {
        $this->expectException(CollectionFactoryException::class);
        $this->expectExceptionMessage('Unsupported collection class `foo`.');

        $this->getFactory()->withCollectionClass('foo');
    }

    public function testWithCollectionClassInterfaceButNotDecorator(): void
    {
        $mock = $this->createMock(CollectionInterface::class);

        $this->expectException(CollectionFactoryException::class);
        $this->expectExceptionMessage(\sprintf('Unsupported collection class `%s`.', $mock::class));

        $this->getFactory()->withCollectionClass($mock::class);
    }

    protected function getFactory(): CollectionFactoryInterface
    {
        return new LoophpCollectionFactory();
    }
}
