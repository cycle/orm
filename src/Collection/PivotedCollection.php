<?php
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

namespace Spiral\ORM\Collection;

use Doctrine\Common\Collections\ArrayCollection;

/**
 * Collection with associated relation context.
 */
class PivotedCollection extends ArrayCollection implements PivotedCollectionInterface
{
    /** @var RelationContext */
    private $relationContext;

    /**
     * @param array           $elements
     * @param RelationContext $relationContext
     */
    public function __construct(array $elements, RelationContext $relationContext)
    {
        parent::__construct($elements);
        $this->relationContext = $relationContext;
    }

    /**
     * Creates a new instance from the specified elements.
     *
     * This method is provided for derived classes to specify how a new
     * instance should be created when constructor semantics have changed.
     *
     * @param array $elements Elements.
     *
     * @return static
     */
    protected function createFrom(array $elements)
    {
        return new static($elements, $this->relationContext);
    }

    /**
     * @return RelationContextInterface
     */
    public function getRelationContext(): RelationContextInterface
    {
        return $this->relationContext;
    }
}