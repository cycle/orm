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
    private $queryScope = [];

    /** @var array */
    private $whereScope = [];

    /** @var array */
    private $orderBy = [];

    /** @var array */
    private $resolved = [];

    /**
     * @param ORMInterface $orm
     * @param string       $target
     * @param array        $queryScope
     * @param array        $whereScope
     * @param array        $orderBy
     */
    public function __construct(
        ORMInterface $orm,
        string $target,
        array $queryScope = [],
        array $whereScope = [],
        array $orderBy = []
    ) {
        $this->orm = $orm;
        $this->target = $target;
        $this->queryScope = $queryScope;
        $this->whereScope = $whereScope;
        $this->orderBy = $orderBy;
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
        return $this->queryScope;
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
        $selector->scope($source->getScope(Selector\Source::DEFAULT_SCOPE));

        $selector->where($this->queryScope + $this->whereScope)->orderBy($this->orderBy);

        $this->resolved = $selector->fetchAll();
        $this->orm = null;

        return $this->resolved;
    }
}