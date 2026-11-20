<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Unit\Collection;

use Cycle\ORM\Collection\ArrayCollectionFactory;
use Cycle\ORM\Collection\CollectionFactoryInterface;
use Testo\Assert;
use Testo\Data\DataProvider;
use Testo\Test;

final class ArrayCollectionFactoryTest extends BaseTest
{
    #[Test]
    public function testGetInterface(): void
    {
        Assert::null($this->getFactory()->getInterface());
    }

    /**
     * @param mixed $data
     */
    #[Test]
    #[DataProvider('collectionDataProvider')]
    public function testCollectShouldReturnArray($data): void
    {
        Assert::same($this->getFactory()->collect($data), [
            'foo' => 'bar',
            'baz' => 'bar',
        ]);
    }

    protected function getFactory(): CollectionFactoryInterface
    {
        return new ArrayCollectionFactory();
    }
}
