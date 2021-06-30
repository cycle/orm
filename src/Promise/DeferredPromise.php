<?php

declare(strict_types=1);

namespace Cycle\ORM\Promise;

use Cycle\ORM\Heap\Node;
use Cycle\ORM\Collection\CollectionFactoryInterface;
use Cycle\ORM\Relation\RelationInterface;

/**
 */
final class DeferredPromise implements Deferred
{
    private PromiseInterface $promise;

    /**
     * @var null|callable
     */
    private $dataFactory;

    public function __construct(PromiseInterface $promise, callable $dataFactory = null)
    {
        $this->promise = $promise;
        $this->dataFactory = $dataFactory;
    }

    public function isLoaded(): bool
    {
        return $this->promise->__loaded();
    }

    public function getData(bool $autoload = true)
    {
        $data = $this->getOrigin($autoload);
        return $this->dataFactory === null ? $data : ($this->dataFactory)($data);
    }

    public function getOrigin(bool $autoload = true)
    {
        if (!$autoload && !$this->promise->__loaded()) {
            throw new \RuntimeException('Deferred object is not resolved.');
        }
        return $this->promise->__resolve();
    }

    public function getScope(): array
    {
        return $this->promise->__scope();
    }

    public function getRole(): string
    {
        return $this->promise->__role();
    }
}
