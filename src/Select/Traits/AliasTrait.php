<?php

declare(strict_types=1);

namespace Cycle\ORM\Select\Traits;

/**
 * @internal
 */
trait AliasTrait
{
    private array $aliasPaths = [];

    private function resolvePath(string $relation): string
    {
        return $this->aliasPaths[$relation] ?? $relation;
    }

    private function registerPath(string $alias, string $relation): void
    {
        $this->aliasPaths[$alias] = $relation;
    }
}
