<?php
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */
declare(strict_types=1);

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
    private $where;

    /** @var mixed */
    private $resolved;

    /**
     * @param ORMInterface $orm
     * @param string       $target
     * @param array        $query
     */
    public function __construct(ORMInterface $orm, string $target, array $query)
    {
        $this->orm = $orm;
        $this->target = $target;
        $this->where = $query;
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
        return $this->where;
    }

    /**
     * @inheritdoc
     */
    public function __resolve()
    {
        if (!is_null($this->orm)) {
            $this->resolved = $this->orm->get($this->target, $this->where, true);
            $this->orm = null;
        }

        return $this->resolved;
    }
}