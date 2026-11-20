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
use Testo\Assert;
use Testo\Data\DataProvider;
use Testo\Expect;
use Testo\Test;

final class LoophpCollectionFactoryTest extends BaseTest
{
    #[Test]
    public function testGetInterface(): void
    {
        Assert::same($this->getFactory()->getInterface(), CollectionInterface::class);
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
        Assert::same($collection->all(false), [
            'foo' => 'bar',
            'baz' => 'bar',
        ]);
    }

    #[Test]
    public function testWithCollectionClassImmutability(): void
    {
        $factory = $this->getFactory();

        $newFactory = $factory->withCollectionClass(Collection::class);

        Assert::notSame($newFactory, $factory);
    }

    #[Test]
    public function testWithCollectionClassNotCollection(): void
    {
        Expect::exception(CollectionFactoryException::class)->withMessageContaining('Unsupported collection class `IteratorIterator`.');

        $this->getFactory()->withCollectionClass(\IteratorIterator::class);
    }

    #[Test]
    #[DataProvider('collectionDataProvider')]
    public function testWithCollectionClassInterface(mixed $data): void
    {
        $collection = $this->getFactory()->withCollectionClass(CollectionInterface::class)->collect($data);

        Assert::instanceOf($collection, Collection::class);
        Assert::same($collection->all(false), ['foo' => 'bar', 'baz' => 'bar']);
    }

    #[Test]
    #[DataProvider('collectionDataProvider')]
    public function testWithCollectionClassCustomClass(mixed $data): void
    {
        $customClass = new class(Collection::empty()) extends CollectionDecorator {};

        $collection = $this->getFactory()->withCollectionClass($customClass::class)->collect($data);

        Assert::instanceOf($collection, $collection::class);
        Assert::same($collection->all(false), ['foo' => 'bar', 'baz' => 'bar']);
    }

    #[Test]
    public function testWithCollectionClassNotClass(): void
    {
        Expect::exception(CollectionFactoryException::class)->withMessageContaining('Unsupported collection class `foo`.');

        $this->getFactory()->withCollectionClass('foo');
    }

    #[Test]
    public function testWithCollectionClassInterfaceButNotDecorator(): void
    {
        $mock = \Mockery::mock(CollectionInterface::class)->shouldIgnoreMissing();

        Expect::exception(CollectionFactoryException::class)->withMessageContaining(\sprintf('Unsupported collection class `%s`.', $mock::class));

        $this->getFactory()->withCollectionClass($mock::class);
    }

    #[Test]
    public function testCollectPivotStorageWithArrayCollection(): void
    {
        $collection = $this->getFactory()->collect(new PivotedStorage($array = [
            'foo' => 'bar',
            'baz' => 'bar',
        ]));

        Assert::instanceOf($collection, LoophpPivotedCollection::class);
        Assert::same($collection->all(false), $array);
    }

    #[Test]
    public function testCollectPivotStorageWithPivotedCollection(): void
    {
        $collection = $this->getFactory()
            ->withCollectionClass(CustomLoophpPivotedCollection::class)
            ->collect(new PivotedStorage($array = [
                'foo' => 'bar',
                'baz' => 'bar',
            ]));

        Assert::instanceOf($collection, CustomLoophpPivotedCollection::class);
        Assert::same($collection->all(false), $array);
    }

    #[Test]
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

        Assert::instanceOf($collection, Collection::class);
        Assert::same($collection->all(false), $array);
    }

    #[Test]
    public function pivotedCollectionKeepsGivenPivotContext(): void
    {
        $entity = new \stdClass();
        $context = new \SplObjectStorage();
        $context[$entity] = ['role' => 'admin'];

        $collection = new LoophpPivotedCollection([$entity], $context);

        Assert::same($collection->getPivot($entity), ['role' => 'admin']);
    }

    #[Test]
    public function pivotedCollectionTracksPivotsOfItsElements(): void
    {
        $linked = new \stdClass();
        $unlinked = new \stdClass();
        $collection = new LoophpPivotedCollection([$linked, $unlinked]);

        $collection->setPivot($linked, ['role' => 'admin']);

        Assert::true($collection->hasPivot($linked));
        Assert::false($collection->hasPivot($unlinked));
        Assert::count($collection->getPivotContext(), 1);
        Assert::same($collection->getPivotContext()[$linked], ['role' => 'admin']);
    }

    #[Test]
    public function withDecoratorClassKeepsOriginalFactory(): void
    {
        $factory = $this->getFactory();

        $factory->withCollectionClass(CustomLoophpPivotedCollection::class);

        Assert::same($factory->collect(['foo' => 'bar'])::class, Collection::class);
    }

    protected function getFactory(): CollectionFactoryInterface
    {
        return new LoophpCollectionFactory();
    }
}

class CustomLoophpPivotedCollection extends LoophpPivotedCollection {}
