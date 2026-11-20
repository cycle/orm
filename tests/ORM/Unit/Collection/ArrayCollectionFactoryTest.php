<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Unit\Collection;

use Cycle\ORM\Collection\ArrayCollectionFactory;
use Cycle\ORM\Collection\CollectionFactoryInterface;

class ArrayCollectionFactoryTest extends BaseTest
{
    #[\Testo\Test]
    public function testGetInterface(): void
    {
        \Testo\Assert::null($this->getFactory()->getInterface());
    }

    /**
     * @param mixed $data
     */
    #[\Testo\Test]
    #[\Testo\Data\DataProvider('collectionDataProvider')]
    public function testCollectShouldReturnArray($data): void
    {
        \Testo\Assert::same($this->getFactory()->collect($data), [
            'foo' => 'bar',
            'baz' => 'bar',
        ]);
    }

    protected function getFactory(): CollectionFactoryInterface
    {
        return new ArrayCollectionFactory();
    }
}
