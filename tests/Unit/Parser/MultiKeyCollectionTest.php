<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Unit\Parser;

use Cycle\Database\Injection\Parameter;
use Cycle\ORM\Parser\MultiKeyCollection;
use Testo\Assert;
use Testo\Codecov\Covers;
use Testo\Test;

#[Test]
#[Covers(MultiKeyCollection::class)]
final class MultiKeyCollectionTest
{
    public function countsItemsByKeyValues(): void
    {
        $collection = $this->collection(['a', 'b'], [['a' => 1, 'b' => 2], ['a' => 1, 'b' => 2], ['a' => 1, 'b' => 3]]);

        Assert::same($collection->getItemsCount('i', [1, 2]), 2);
        Assert::same($collection->getItemsCount('i', [1, 4]), 0);
    }

    public function criteriaUsesPlainValueForSingleLastKey(): void
    {
        $collection = $this->collection(['a', 'b'], [['a' => 1, 'b' => 2]]);

        Assert::same($collection->getCriteria('i'), [['a' => 1, 'b' => 2]]);
    }

    public function criteriaGroupsLastKeyValuesIntoParameter(): void
    {
        $collection = $this->collection(['a', 'b'], [['a' => 1, 'b' => 2], ['a' => 1, 'b' => 3]]);

        $criteria = $collection->getCriteria('i');

        Assert::count($criteria, 1);
        Assert::same($criteria[0]['a'], 1);
        Assert::instanceOf($criteria[0]['b'], Parameter::class);
        Assert::same($criteria[0]['b']->getValue(), [2, 3]);
    }

    public function criteriaWalksEveryKeyOfLongIndex(): void
    {
        $collection = $this->collection(['a', 'b', 'c'], [
            ['a' => 1, 'b' => 2, 'c' => 3],
            ['a' => 1, 'b' => 4, 'c' => 5],
        ]);

        Assert::same($collection->getCriteria('i'), [
            ['a' => 1, 'b' => 2, 'c' => 3],
            ['a' => 1, 'b' => 4, 'c' => 5],
        ]);
    }

    /**
     * @param list<non-empty-string> $keys
     * @param list<array<non-empty-string, scalar>> $items
     */
    private function collection(array $keys, array $items): MultiKeyCollection
    {
        $collection = new MultiKeyCollection();
        $collection->createIndex('i', $keys);
        foreach ($items as $item) {
            $collection->addItem('i', $item);
        }
        return $collection;
    }
}
