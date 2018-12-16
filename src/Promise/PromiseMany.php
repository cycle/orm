<?php
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

namespace Spiral\Cycle\Promise;

use Spiral\Cycle\Exception\RelationException;
use Spiral\Cycle\ORMInterface;
use Spiral\Cycle\Selector;
use Spiral\Cycle\Selector\SourceInterface;

/**
 * Promises the selection of the
 */
class PromiseMany implements PromiseInterface
{
    /** @var ORMInterface @internal */
    private $orm;

    /** @var string */
    private $target;

    /** @var array */
    private $query = [];

    /** @var array */
    private $where = [];

    /** @var Selector\ScopeInterface|null */
    private $scope;

    /** @var array */
    private $resolved = [];

    /**
     * @param ORMInterface $orm
     * @param string       $target
     * @param array        $query
     * @param array        $where
     */
    public function __construct(ORMInterface $orm, string $target, array $query = [], array $where = [])
    {
        $this->orm = $orm;
        $this->target = $target;
        $this->query = $query;
        $this->where = $where;
    }

    /**
     * @param Selector\ScopeInterface $scope
     */
    public function setScope(?Selector\ScopeInterface $scope)
    {
        $this->scope = $scope;
    }

    /**
     * @inheritdoc
     */
    public function __loaded(): bool
    {
        return empty($this->orm);
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
        return $this->query;
    }

    /**
     * @inheritdoc
     */
    public function __resolve()
    {
        if (is_null($this->orm)) {
            return $this->resolved;
        }

        $source = $this->orm->getMapper($this->target);
        if (!$source instanceof SourceInterface) {
            throw new RelationException("ManyToMany relation can only work with SelectableInterface mappers");
        }

        $selector = new Selector($this->orm, $this->target);
        $this->resolved = $selector->scope($this->scope)->where($this->query + $this->where)->fetchAll();

        $this->orm = null;

        return $this->resolved;
    }
}