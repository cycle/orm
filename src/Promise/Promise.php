<?php
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

namespace Spiral\ORM\Promise;

use Spiral\ORM\PromiseInterface;

class Promise implements PromiseInterface
{
    private $entity;

    private $promise;

    public $context;

    public function __construct(array $context, callable $promise)
    {
        $this->promise = $promise;
        $this->context = $context;
    }

    public function __resolve()
    {
        if (!is_null($this->promise)) {
            $this->entity = call_user_func($this->promise);
            $this->promise = null;
        }

        return $this->entity;
    }

    public function __context(): array
    {
        return $this->context;
    }

    public function __get($name)
    {
        return $this->__resolve()->$name;
    }

    public function __set($name, $value)
    {
        $this->__resolve()->$name = $value;
    }

    public function __call($name, $arguments)
    {
        return $this->__resolve()->__call($name, $arguments);
    }
}