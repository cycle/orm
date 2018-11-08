<?php
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

namespace Spiral\Treap\Schema;

/**
 * Static list of declarations.
 */
class StaticLocator implements LocatorInterface
{
    /** @var EntityInterface[] */
    private $declarations = [];

    /**
     * @param EntityInterface $schema
     */
    public function add(EntityInterface $schema)
    {
        $this->declarations[] = $schema;
    }

    /**
     * @inheritdoc
     */
    public function getDeclarations(): array
    {
        return $this->declarations;
    }
}