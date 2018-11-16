<?php
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

namespace Spiral\ORM\Collection;

interface RelationContextInterface
{
    public function has($entity);

    public function get($entity);

    public function set($entity, $context);
}