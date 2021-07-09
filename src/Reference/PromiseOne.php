<?php

declare(strict_types=1);

namespace Cycle\ORM\Reference;

use Cycle\ORM\ORMInterface;

/**
 * Promises one entity and resolves the result via ORM heap or entity repository.
 */
final class PromiseOne implements PromiseInterface
{
    /** @internal */
    private ?ORMInterface $orm;

    private string $target;

    private array $scope;

    private ?object $resolved = null;

    public function __construct(ORMInterface $orm, string $target, array $scope)
    {
        $this->orm = $orm;
        $this->target = $target;
        $this->scope = $scope;
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
        return $this->scope;
    }

    public function __resolve()
    {
        if ($this->orm === null) {
            return $this->resolved;
        }

        if (count($this->scope) !== 1) {
            $this->resolved = $this->orm->getRepository($this->target)->findOne($this->scope);
        } elseif ($this->scope === []) {
            // nothing to proxy to
            $this->orm = null;
        } else {
            $this->resolved = $this->orm->get($this->target, $this->scope, true);
        }

        $this->orm = null;

        return $this->resolved;
    }
}
