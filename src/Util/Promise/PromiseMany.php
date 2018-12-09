<?php
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

namespace Spiral\ORM\Util\Promise;

use Spiral\ORM\PromiseInterface;
use Spiral\ORM\Selector;

/**
 * Promises the selection of the
 */
class PromiseMany implements PromiseInterface
{
    /** @var Selector|null */
    private $selector;

    /** @var array */
    private $scope = [];

    /** @var array */
    private $whereScope = [];

    /** @var array */
    private $orderBy = [];

    /** @var array */
    private $resolved = [];

    /**
     * @param Selector $selector
     * @param array    $scope
     * @param array    $whereScope
     * @param array    $orderBy
     */
    public function __construct(Selector $selector, array $scope = [], array $whereScope = [], array $orderBy = [])
    {
        $this->selector = $selector;
        $this->scope = $scope;
        $this->whereScope = $whereScope;
        $this->orderBy = $orderBy;
    }

    /**
     * @inheritdoc
     */
    public function __loaded(): bool
    {
        return empty($this->selector);
    }

    /**
     * @inheritdoc
     */
    public function __role(): string
    {
        return $this->selector->getLoader()->getTarget();
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
        if (is_null($this->selector)) {
            return $this->resolved;
        }

        $this->resolved = $this->selector
            ->where($this->scope + $this->whereScope)
            ->orderBy($this->orderBy)
            ->fetchAll();

        $this->selector = null;

        return $this->resolved;
    }
}