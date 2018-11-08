<?php
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

namespace Spiral\Treap;

class ORM implements ORMInterface
{
    public function getSchema(string $class): SchemaInterface
    {
        // TODO: Implement getSchema() method.
    }

    public function make(
        string $class,
        array $data = [],
        int $state = MapperInterface::STATE_NEW,
        bool $cache = false
    ) {
        // TODO: Implement make() method.
    }
}