<?php

declare(strict_types=1);

namespace Cycle\ORM\Promise;

final class DeferredStatic implements Deferred
{
    private $data;
    /**
     * @var null|callable
     */
    private $dataFactory;

    public function __construct($data, ?callable $dataFactory)
    {
        $this->data = $data;
        $this->dataFactory = $dataFactory;
    }

    public function isLoaded(): bool
    {
        return true;
    }

    public function getData(bool $autoload = true)
    {
        return $this->dataFactory === null ? $this->data : ($this->dataFactory)($this->data);
    }

    public function getOrigin(bool $autoload = true)
    {
        return $this->data;
    }

    // public function getScope(): array
    // {
    //     // todo
    // }
    //
    // public function getRole(): string
    // {
    //     // todo
    // }
}
