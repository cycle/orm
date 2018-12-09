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
use Spiral\ORM\SchemaInterface;

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

    private $mrole;

    /**
     * @param ORMInterface $orm
     * @param string       $class
     * @param array        $scope
     */
    public function __construct(ORMInterface $orm, string $class, array $scope, $mrole = null)
    {
        $this->orm = $orm;
        $this->class = $class;
        $this->scope = $scope;
        $this->mrole = $mrole;
    }

    /**
     * @inheritdoc
     */
    public function __loaded(): bool
    {
        return empty($this->promise);
    }

    public function __role(): string
    {


        return $this->mrole ?? $this->orm->getSchema()->define($this->class, SchemaInterface::ALIAS);
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
            $this->result = $this->orm->fetchOne($this->class, $this->scope, true);
            $this->orm = null;
        }

        return $this->result;
    }
}