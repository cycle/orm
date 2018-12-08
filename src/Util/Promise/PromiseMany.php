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

class PromiseMany implements PromiseInterface
{
    /**
     * @invisible
     * @var ORMInterface|null
     */
    private $orm;

    /** @var string|null */
    private $class;

    /** @var array */
    private $scope;

    private $orderBy;

    /** @var mixed */
    private $result;

    /**
     * PromiseMany constructor.
     *
     * @param ORMInterface $orm
     * @param string       $class
     * @param array        $scope
     * @param array        $orderBy
     */
    public function __construct(ORMInterface $orm, string $class, array $scope, array $orderBy = [])
    {
        $this->orm = $orm;
        $this->class = $class;
        $this->scope = $scope;
        $this->orderBy = $orderBy;
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

    public function __role(): string
    {
        return $this->class;
    }

    /**
     * @inheritdoc
     */
    public function __resolve()
    {
        if (!is_null($this->orm)) {
            // todo: need it better
            $scope = [];
            foreach ($this->scope as $key => $value) {
                $scope[str_replace('@.', '', $key)] = $value;
            }

            $this->result = $this->orm->getMapper($this->class)->getRepository()->findAll($scope);
            $this->orm = null;
        }

        return $this->result;
    }
}