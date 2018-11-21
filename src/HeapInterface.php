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

    public function get($entity): ?StateInterface;

    public function attach($entity, StateInterface $state, array $paths = []);

    public function onUpdate($entity, callable $handler);

    public function detach($entity);

    public function reset();
}