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
    private $schema = [];

    public function setSchema(string $class, Schema $schema)
    {
        $this->schema[$class] = $schema;
    }

    public function getSchema(string $class): Schema
    {
        return $this->schema[$class];
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