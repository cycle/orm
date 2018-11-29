<?php
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

namespace Spiral\ORM\Util;

use Spiral\ORM\MapperInterface;
use Spiral\ORM\PromiseInterface;

/**
 * Provides the ability to resolve data on-demand.
 */
class Promise implements PromiseInterface
{
    /** @var mixed */
    private $resolved;

    /** @var callable */
    private $promise;

    /** @var array */
    private $scope;

    /**
     * @param array    $scope
     * @param callable $promise
     */
    public function __construct(array $scope, callable $promise)
    {
        $this->promise = $promise;
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
        if (!is_null($this->promise)) {
            $this->resolved = call_user_func($this->promise, $this->scope);
            $this->promise = null;
        }

        return $this->resolved;
    }
}