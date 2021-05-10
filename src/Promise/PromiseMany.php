<?php

declare(strict_types=1);

namespace Cycle\ORM\Promise;

use Cycle\ORM\ORMInterface;

/**
 * Promises the selection of the
 */
final class PromiseMany implements PromiseInterface
{
    /** @internal */
    private ?ORMInterface $orm;

    private string $target;

    private array $query;

    private array $where;

    private array $resolved = [];

    public function __construct(ORMInterface $orm, string $target, array $query = [], array $where = [])
    {
        $this->orm = $orm;
        $this->target = $target;
        $this->query = $query;
        $this->where = $where;
    }

    public function __loaded(): bool
    {
        return $this->orm === null;
    }

    public function __role(): string
    {
        return $this->target;
    }

    public function __scope(): array
    {
        return $this->query;
    }

    public function __resolve()
    {
        if ($this->orm === null) {
            return $this->resolved;
        }

        if ($this->query === []) {
            // nothing to proxy to
            $this->orm = null;

            return [];
        }

        foreach ($this->orm->getRepository($this->target)->findAll($this->query + $this->where) as $item) {
            $this->resolved[] = $item;
        }
        $this->orm = null;

        return $this->resolved;
    }
}
