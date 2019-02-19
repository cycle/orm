<?php
declare(strict_types=1);
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

namespace Cycle\ORM\Promise;

use Cycle\ORM\ORMInterface;
use Cycle\ORM\Select;

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

    /** @var Select\ConstrainInterface|null */
    private $constrain;

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
     * @param Select\ConstrainInterface $constrain
     */
    public function setConstrain(?Select\ConstrainInterface $constrain)
    {
        $this->constrain = $constrain;
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

        $selector = new Select($this->orm, $this->target);
        $this->resolved = $selector->constrain($this->constrain)->where($this->query + $this->where)->fetchAll();

        $this->orm = null;

        return $this->resolved;
    }
}