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
use Spiral\Cycle\Select;

/**
 * Promises one entity and resolves the result via ORM heap or entity repository.
 */
class PromiseOne implements PromiseInterface
{
    /** @var ORMInterface|null */
    private $orm;

    /** @var string|null */
    private $target;

    /** @var Select\ConstrainInterface|null */
    private $constrain;

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
        return $this->scope;
    }

    /**
     * @inheritdoc
     */
    public function __resolve()
    {
        if (!is_null($this->orm)) {
            // entity has already been loaded in memory
            if (!is_null($e = $this->orm->getHeap()->find($this->target, $this->scope))) {
                $this->orm = null;
                return $this->resolved = $e;
            }

            // Fetching from the database
            $select = new Select($this->orm, $this->target);
            $this->resolved = $select->constrain($this->constrain)->fetchOne($this->scope);

            $this->orm = null;
        }

        return $this->resolved;
    }
}