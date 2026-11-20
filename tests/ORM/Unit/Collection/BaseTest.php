<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Unit\Collection;

use Cycle\ORM\Collection\CollectionFactoryInterface;
use PHPUnit\Framework\TestCase;

abstract class BaseTest
{
    private CollectionFactoryInterface $factory;

    public function collectionDataProvider()
    {
        return [
            'array' => [
                [
                    'foo' => 'bar',
                    'baz' => 'bar',
                ],
            ],
            'generator' => [
                $this->generatorArray(),
            ],
            'traversable' => [
                new \ArrayIterator([
                    'foo' => 'bar',
                    'baz' => 'bar',
                ]),
            ],
        ];
    }

    #[\Testo\Lifecycle\BeforeTest]
    protected function setUp(): void
    {
        $this->factory = $this->getFactory();
    }

    abstract protected function getFactory(): CollectionFactoryInterface;

    private function generatorArray()
    {
        yield 'foo' => 'bar';
        yield 'baz' => 'bar';
    }
}
