<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Util;

use Closure;
use Spiral\Core\Exception\Container\AutowireException;
use Spiral\Core\FactoryInterface;

final class SimpleFactory implements FactoryInterface
{
    private array $definitions;
    private Closure $factory;

    /**
     * @param array $definitions List of items that will be cloned
     * @param Closure|null $factory Should be closure that works
     *        like FactoryInterface::make(string $alias, array $parameters): mixed
     */
    public function __construct(array $definitions = [], Closure $factory = null)
    {
        $this->definitions = $definitions;
        $this->factory = $factory ?? static function (string $alias, array $parameters = []): void {
                throw new AutowireException("Factory can't make `$alias`.");
            };
    }

    public function make(string $alias, array $parameters = []): mixed
    {
        if (!\array_key_exists($alias, $this->definitions)) {
            $this->definitions[$alias] = ($this->factory)($alias, $parameters);
        }
        return clone $this->definitions[$alias];
    }
}
