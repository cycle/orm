<?php
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

namespace Spiral\ORM;

// todo: ID path will be later
// todo: handlers
interface HeapInterface
{
    public function has($entity): bool;

    public function get($entity): ?Node;

    public function attach($entity, Node $state, array $index = []);

    public function detach($entity);

    public function reset();
}