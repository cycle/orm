<?php
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

namespace Spiral\Cycle\Promise;

use Spiral\Cycle\ORMInterface;

/**
 * Promises one entity and resolves the result via ORM heap or entity repository.
 */
class PromiseOne implements PromiseInterface
{
    /** @var ORMInterface|null */
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
     * @param array        $queryScope
     */
    public function __construct(ORMInterface $orm, string $target, array $queryScope)
    {
        $this->orm = $orm;
        $this->target = $target;
        $this->scope = $queryScope;
    }

    /**
     * @inheritdoc
     */
    public function __loaded(): bool
    {
        return empty($this->promise);
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
        if (!is_null($this->orm)) {
            $this->resolved = $this->orm->get($this->target, $this->scope, true);
            $this->orm = null;
        }

        return $this->resolved;
    }
}