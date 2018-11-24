<?php
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

namespace Spiral\ORM;

interface PromiseInterface
{
    public function __context(): array;

    public function __resolve();

    public function __loaded(): bool;
}