<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Unit\Collection;

use Cycle\ORM\Collection\CollectionFactoryInterface;
use Cycle\ORM\Collection\LoophpCollectionFactory;
use Cycle\ORM\Collection\Pivoted\LoophpPivotedCollection;
use Cycle\ORM\Collection\Pivoted\PivotedStorage;
use Cycle\ORM\Exception\CollectionFactoryException;
use loophp\collection\Collection;
use loophp\collection\CollectionDecorator;
use loophp\collection\Contract\Collection as CollectionInterface;

class LoophpCollectionFactoryTest extends BaseTest
{
    #[\Testo\Test]
    public function testGetInterface(): void
    {
        \Testo\Assert::same($this->getFactory()->getInterface(), CollectionInterface::class);
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
        \Testo\Assert::same($collection->all(false), [
            'foo' => 'bar',
            'baz' => 'bar',
        ]);
    }

    #[\Testo\Test]
    public function testWithCollectionClassImmutability(): void
    {
        $factory = $this->getFactory();

        $newFactory = $factory->withCollectionClass(Collection::class);

        \Testo\Assert::notSame($newFactory, $factory);
    }

    #[\Testo\Test]
    public function testWithCollectionClassNotCollection(): void
    {
        \Testo\Expect::exception(CollectionFactoryException::class)->withMessageContaining('Unsupported collection class `IteratorIterator`.');

        $this->getFactory()->withCollectionClass(\IteratorIterator::class);
    }

    #[\Testo\Test]
    #[\Testo\Data\DataProvider('collectionDataProvider')]
    public function testWithCollectionClassInterface(mixed $data): void
    {
        $collection = $this->getFactory()->withCollectionClass(CollectionInterface::class)->collect($data);

        \Testo\Assert::instanceOf($collection, Collection::class);
        \Testo\Assert::same($collection->all(false), ['foo' => 'bar', 'baz' => 'bar']);
    }

    #[\Testo\Test]
    #[\Testo\Data\DataProvider('collectionDataProvider')]
    public function testWithCollectionClassCustomClass(mixed $data): void
    {
        $customClass = new class(Collection::empty()) extends CollectionDecorator {};

        $collection = $this->getFactory()->withCollectionClass($customClass::class)->collect($data);

        \Testo\Assert::instanceOf($collection, $collection::class);
        \Testo\Assert::same($collection->all(false), ['foo' => 'bar', 'baz' => 'bar']);
    }

    #[\Testo\Test]
    public function testWithCollectionClassNotClass(): void
    {
        \Testo\Expect::exception(CollectionFactoryException::class)->withMessageContaining('Unsupported collection class `foo`.');

        $this->getFactory()->withCollectionClass('foo');
    }

    #[\Testo\Test]
    public function testWithCollectionClassInterfaceButNotDecorator(): void
    {
        $mock = \Mockery::mock(CollectionInterface::class)->shouldIgnoreMissing();

        \Testo\Expect::exception(CollectionFactoryException::class)->withMessageContaining(\sprintf('Unsupported collection class `%s`.', $mock::class));

        $this->getFactory()->withCollectionClass($mock::class);
    }

    #[\Testo\Test]
    public function testCollectPivotStorageWithArrayCollection(): void
    {
        $collection = $this->getFactory()->collect(new PivotedStorage($array = [
            'foo' => 'bar',
            'baz' => 'bar',
        ]));

        \Testo\Assert::instanceOf($collection, LoophpPivotedCollection::class);
        \Testo\Assert::same($collection->all(false), $array);
    }

    #[\Testo\Test]
    public function testCollectPivotStorageWithPivotedCollection(): void
    {
        $collection = $this->getFactory()
            ->withCollectionClass(CustomLoophpPivotedCollection::class)
            ->collect(new PivotedStorage($array = [
                'foo' => 'bar',
                'baz' => 'bar',
            ]));

        \Testo\Assert::instanceOf($collection, CustomLoophpPivotedCollection::class);
        \Testo\Assert::same($collection->all(false), $array);
    }

    #[\Testo\Test]
    public function testCollectPivotStorageDecoratorIsNotExists(): void
    {
        $factory = $this->getFactory();
        $ref = new \ReflectionProperty($factory, 'decoratorExists');
        PHP_VERSION_ID < 80100 and $ref->setAccessible(true);
        $ref->setValue($factory, false);

        $collection = $factory->collect(new PivotedStorage($array = [
            'foo' => 'bar',
            'baz' => 'bar',
        ]));

        \Testo\Assert::instanceOf($collection, Collection::class);
        \Testo\Assert::same($collection->all(false), $array);
    }

    protected function getFactory(): CollectionFactoryInterface
    {
        return new LoophpCollectionFactory();
    }
}

class CustomLoophpPivotedCollection extends LoophpPivotedCollection {}
