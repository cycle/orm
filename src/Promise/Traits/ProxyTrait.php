<?php
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */
declare(strict_types=1);

namespace Spiral\Cycle\Promise\Traits;

use Spiral\Cycle\Promise\PromiseInterface;

trait ProxyTrait
{
    /** @var PromiseInterface */
    protected $promise;

    /**
     * @return bool
     */
    public function __loaded(): bool
    {
        return $this->promise->__loaded();
    }

    /**
     * @return string
     */
    public function __role(): string
    {
        return $this->promise->__role();
    }

    /**
     * @return array
     */
    public function __scope(): array
    {
        return $this->promise->__scope();
    }

    /**
     * @return mixed|null
     */
    public function __resolve()
    {
        return $this->promise->__resolve();
    }
}