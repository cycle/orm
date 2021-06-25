<?php

declare(strict_types=1);

namespace Cycle\ORM\Promise;

interface Deferred
{
    public function isLoaded(): bool;

    /**
     * @return mixed
     */
    public function getData(bool $autoload = true);

    /**
     * @return mixed
     */
    public function getOrigin(bool $autoload = true);
    //
    // public function getScope(): array;
    //
    // public function getRole(): string;
}
