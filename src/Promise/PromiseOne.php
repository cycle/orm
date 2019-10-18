<?php

/**
 * Cycle DataMapper ORM
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

declare(strict_types=1);

namespace Cycle\ORM\Promise;

use Cycle\ORM\ORMInterface;

/**
 * Promises one entity and resolves the result via ORM heap or entity repository.
 */
final class PromiseOne implements PromiseInterface
{
    /** @var ORMInterface|null @internal */
    private $orm;

    /** @var string|null */
    private $target;

    /** @var array */
    private $scope;

    /** @var mixed */
    private $resolved;

    /**
     * @param ORMInterface $orm
     * @param string       $target
     * @param array        $scope
     */
    public function __construct(ORMInterface $orm, string $target, array $scope)
    {
        $this->orm = $orm;
        $this->target = $target;
        $this->scope = $scope;
    }

    /**
     * @inheritdoc
     */
    public function __loaded(): bool
    {
        return $this->orm === null;
    }

    /**
     * @inheritdoc
     */
    public function __role(): string
    {
        return $this->target;
    }

    /**
     * @inheritdoc
     */
    public function __scope(): array
    {
        return $this->scope;
    }

    /**
     * @inheritdoc
     */
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
