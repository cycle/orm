<?php

declare(strict_types=1);

namespace Cycle\ORM\Reference;

/**
 * Reference without scope. Now it works for resolving of empty collection or null.
 * Todo: make deferred scope pull from entity
 */
final class DeferredReference extends Reference
{
}
