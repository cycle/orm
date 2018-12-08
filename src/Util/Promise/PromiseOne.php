<?php
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

namespace Spiral\ORM\Util\Promise;

use Spiral\ORM\ORMInterface;
use Spiral\ORM\PromiseInterface;

/**
 * Promises one class and resolves the result via ORM heap or entity repository.
 */
class PromiseOne implements PromiseInterface
{
    /** @var ORMInterface|null */
    private $orm;

    /** @var string|null */
    private $class;

    /** @var array */
    private $scope;

    /** @var mixed */
    private $result;

    /**
     * @param ORMInterface $orm
     * @param string       $class
     * @param array        $scope
     */
    public function __construct(ORMInterface $orm, string $class, array $scope)
    {
        $this->orm = $orm;
        $this->class = $class;
        $this->scope = $scope;
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
            $this->result = $this->orm->locateOne($this->class, $this->scope, true);
            $this->orm = $this->class = null;
        }

        return $this->result;
    }
}