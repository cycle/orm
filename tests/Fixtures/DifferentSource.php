<?php

// phpcs:ignoreFile
declare(strict_types=1);

namespace Cycle\ORM\Tests\Fixtures;

use Cycle\Database\DatabaseInterface;
use Cycle\ORM\Select\ScopeInterface;
use Cycle\ORM\Select\SourceInterface;

class DifferentSource implements SourceInterface
{
    public function getDatabase(): DatabaseInterface
    {
        throw new \RuntimeException('Not implemented.');
    }

    public function getTable(): string
    {
        throw new \RuntimeException('Not implemented.');
    }

    public function withScope(?ScopeInterface $scope): SourceInterface
    {
        return $this;
    }

    public function getScope(): ?ScopeInterface
    {
        return null;
    }
}
