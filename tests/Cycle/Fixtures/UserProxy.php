<?php
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */
declare(strict_types=1);

namespace Spiral\Cycle\Tests\Fixtures;

use Spiral\Cycle\Promise\PromiseInterface;

class UserProxy extends User implements PromiseInterface
{
    private $promise;

    public function __construct(PromiseInterface $promise)
    {
        $this->promise = $promise;
    }

    public function getID()
    {
        return $this->promise->__resolve()->id;
    }

    public function __loaded(): bool
    {
        return $this->promise->__loaded();
    }

    public function __role(): string
    {
        return $this->promise->__role();
    }

    public function __scope(): array
    {
        return $this->promise->__scope();
    }

    public function __resolve()
    {
        return $this->promise->__resolve();
    }
}