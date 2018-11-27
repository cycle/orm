<?php
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

namespace Spiral\ORM\Util;

use Spiral\ORM\PromiseInterface;

class Promise implements PromiseInterface
{
    /** @var mixed */
    private $resolved;

    /** @var callable */
    private $promise;

    /** @var array */
    private $context;

    /**
     * @inheritdoc
     */
    public function __construct(array $context, callable $promise)
    {
        $this->promise = $promise;
        $this->context = $context;
    }

    /**
     * @inheritdoc
     */
    public function __loaded(): bool
    {
        return !empty($this->promise);
    }

    /**
     * @inheritdoc
     */
    public function __context(): array
    {
        return $this->context;
    }

    /**
     * @inheritdoc
     */
    public function __resolve()
    {
        if (!is_null($this->promise)) {
            $this->resolved = call_user_func($this->promise);
            $this->promise = null;
        }

        return $this->resolved;
    }
}