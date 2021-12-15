<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Unit\Collection;

use Cycle\ORM\Collection\CollectionFactoryInterface;
use PHPUnit\Framework\TestCase;

abstract class BaseTest extends TestCase
{
    abstract protected function getFactory(): CollectionFactoryInterface;

    protected function setUp(): void
    {
        parent::setUp();
        $this->factory = $this->getFactory();
    }

    private function generatorArray()
    {
        yield 'foo' => 'bar';
        yield 'baz' => 'bar';
    }

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
                new class implements \Iterator {
                    private array $array = [
                        'foo' => 'bar',
                        'baz' => 'bar',
                    ];

                    public function __construct()
                    {
                        $this->position = 0;
                    }

                    public function current()
                    {
                        return current($this->array);
                    }

                    public function next()
                    {
                        return next($this->array);
                    }

                    public function key()
                    {
                        return key($this->array);
                    }

                    public function valid()
                    {
                        return key($this->array) !== null;
                    }

                    public function rewind()
                    {
                        return reset($this->array);
                    }
                },
            ],
        ];
    }
}
