<?php

/**
 * Cycle DataMapper ORM
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

declare(strict_types=1);

namespace Cycle\ORM\Promise;

use Cycle\ORM\Iterator;
use Cycle\ORM\ORMInterface;
use Cycle\ORM\Select;

/**
 * Promises the selection of the
 */
final class PromiseMany implements PromiseInterface
{
    /** @var ORMInterface @internal */
    private $orm;

    /** @var string */
    private $target;

    /** @var array */
    private $query = [];

    /** @var array */
    private $where = [];

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
        return $this->query;
    }

    /**
     * @inheritdoc
     */
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

        $select = new Select($this->orm, $this->target);
        $select->scope($this->orm->getSource($this->target)->getConstrain());
        $select->andWhere($this->query + $this->where);

        $iterator = new Iterator($this->orm, $this->target, $select->fetchData(), true);

        $this->resolved = \iterator_to_array($iterator, false);
        $this->orm = null;

        return $this->resolved;
    }
}
