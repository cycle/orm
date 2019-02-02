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
use Spiral\Cycle\Promise\Traits\ProxyTrait;

class ProfileProxy extends Profile implements PromiseInterface
{
    use ProxyTrait;

    public function __construct(PromiseInterface $promise)
    {
        $this->promise = $promise;
    }

    public function getID()
    {
        return $this->__resolve()->id;
    }

    public function getImage()
    {
        return $this->__resolve()->image;
    }
}