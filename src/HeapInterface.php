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

    public function get($entity): ?State;

    public function attach($entity, State $state);

    public function onUpdate($entity, callable $handler);

    public function detach($entity);

    public function reset();
}