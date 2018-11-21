<?php
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

namespace Spiral\ORM\Traits;

trait ReferenceTrait
{
    /** @var int */
    private $numReferences = 1;

    /**
     * Add reference to the related entity.
     */
    public function addReference()
    {
        $this->numReferences++;
    }

    /**
     * Un-claim reference to the related entity.
     */
    public function decReference()
    {
        $this->numReferences--;
    }

    /**
     * Check if related entity has any references.
     *
     * @return bool
     */
    public function hasReferences(): bool
    {
        return $this->numReferences > 0;
    }
}