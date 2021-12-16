<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Unit\Collection;

use Cycle\ORM\Collection\ArrayCollectionFactory;
use Cycle\ORM\Collection\CollectionFactoryInterface;

class ArrayCollectionFactoryTest extends BaseTest
{
    public function testGetInterface(): void
    {
        $this->assertNull($this->getFactory()->getInterface());
    }

    /**
     * @dataProvider collectionDataProvider
     */
    public function testCollectShouldReturnArray($data): void
    {
        $this->assertSame([
            'foo' => 'bar',
            'baz' => 'bar',
        ], $this->getFactory()->collect($data));
    }

    protected function getFactory(): CollectionFactoryInterface
    {
        return new ArrayCollectionFactory();
    }
}
