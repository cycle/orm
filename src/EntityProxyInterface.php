<?php

declare(strict_types=1);

namespace Cycle\ORM;

/**
 * A proxy entity is an object using instead of the “real” object. The proxy entity can add behavior to the object
 * being proxied without that object being aware of it. Every proxy entity implements EntityProxyInterface.
 * The interface can be used to determine whether an entity is the "real" object or proxied entity.
 *
 * @internal
 */
interface EntityProxyInterface {}
