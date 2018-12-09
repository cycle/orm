<?php
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

namespace Spiral\ORM\Util\Promise;

use Spiral\ORM\Mapper\SelectableInterface;
use Spiral\ORM\PromiseInterface;

/**
 * Promises the selection of the
 */
class PromiseMany implements PromiseInterface
{
    /** @var SelectableInterface|null */
    private $mapper;

    /** @var array */
    private $scope = [];

    /** @var array */
    private $orderBy = [];

    /** @var array */
    private $result = [];

    /**
     * @param SelectableInterface $mapper
     * @param array               $scope
     * @param array               $orderBy
     */
    public function __construct(SelectableInterface $mapper, array $scope = [], array $orderBy = [])
    {
        $this->mapper = $mapper;
        $this->scope = $scope;
        $this->orderBy = $orderBy;
    }

    /**
     * @inheritdoc
     */
    public function __loaded(): bool
    {
        return empty($this->mapper);
    }

    /**
     * @inheritdoc
     */
    public function __role(): string
    {
        return $this->mapper->getRole();
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
        if (is_null($this->mapper)) {
            return $this->result;
        }

        $this->result = $this->mapper->getSelector()->where($this->scope)->orderBy($this->orderBy)->fetchAll();
        $this->mapper = null;

        return $this->result;
    }
}