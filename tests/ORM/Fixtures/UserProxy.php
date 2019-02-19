<?php
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */
declare(strict_types=1);

namespace Cycle\ORM\Tests\Fixtures;

use Cycle\ORM\ORMInterface;
use Cycle\ORM\Promise\PromiseInterface;
use Cycle\ORM\Select;

class UserProxy extends User implements PromiseInterface
{
    /** @var ORMInterface|null @internal */
    private $__orm;

    /** @var string|null */
    private $__target;

    /** @var array */
    private $__scope;

    /** @var User */
    private $__resolved;

    /**
     * @param ORMInterface $orm
     * @param string       $target
     * @param array        $scope
     */
    public function __construct(ORMInterface $orm, string $target, array $scope)
    {
        $this->__orm = $orm;
        $this->__target = $target;
        $this->__scope = $scope;
    }

    /**
     * @inheritdoc
     */
    public function __loaded(): bool
    {
        return empty($this->__orm);
    }

    /**
     * @inheritdoc
     */
    public function __role(): string
    {
        return $this->__target;
    }

    /**
     * @inheritdoc
     */
    public function __scope(): array
    {
        return $this->__scope;
    }

    /**
     * @inheritdoc
     */
    public function __resolve()
    {
        if (!is_null($this->__orm)) {
            $key = key($this->__scope);
            $value = $this->__scope[$key];

            // entity has already been loaded in memory
            if (!is_null($e = $this->__orm->getHeap()->find($this->__target, $key, $value))) {
                $this->__orm = null;
                return $this->__resolved = $e;
            }

            // Fetching from the database
            $select = new Select($this->__orm, $this->__target);
            $this->__resolved = $select->constrain(
                $this->__orm->getSource($this->__target)->getConstrain()
            )->fetchOne($this->__scope);

            $this->__orm = null;
        }

        return $this->__resolved;
    }

    public function getID()
    {
        return $this->__resolve()->getID();
    }

    public function addComment(Comment $c)
    {
        return $this->__resolve()->addComment($c);
    }
}