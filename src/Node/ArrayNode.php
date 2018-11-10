<?php
/**
 * Spiral, Core Components
 *
 * @author Wolfy-J
 */

namespace Spiral\ORM\Node;

use Spiral\ORM\Exception\NodeException;

/**
 * Parses multiple sub children and mount them under parent node.
 */
class ArrayNode extends AbstractNode implements ArrayInterface
{
    /** @var string */
    protected $innerKey;

    /**
     * @param array       $columns
     * @param string      $primaryKey
     * @param string      $innerKey Inner relation key (for example user_id)
     * @param string|null $outerKey Outer (parent) relation key (for example id = parent.id)
     */
    public function __construct(
        array $columns,
        string $primaryKey,
        string $innerKey,
        string $outerKey
    ) {
        parent::__construct($columns, $outerKey);
        $this->setDuplicateCriteria($primaryKey);

        $this->innerKey = $innerKey;
    }

    /**
     * {@inheritdoc}
     */
    protected function push(array &$data)
    {
        if (empty($this->parent)) {
            throw new NodeException("Unable to register data tree, parent is missing.");
        }

        if (is_null($data[$this->innerKey])) {
            // no data was parsed
            return;
        }

        $this->parent->mountArray(
            $this->container,
            $this->outerKey,
            $data[$this->innerKey],
            $data
        );
    }
}