<?php

/**
 * Spiral, Core Components
 *
 * @author Wolfy-J
 */

declare(strict_types=1);

namespace Cycle\ORM\Parser;

use Cycle\ORM\Exception\ParserException;

/**
 * Parses multiple sub children and mount them under parent node.
 *
 * @internal
 */
final class ArrayNode extends AbstractNode
{
    /** @var string[] */
    protected $innerKeys;

    /**
     * @param array              $columns
     * @param array|string       $primaryKey
     * @param array|string       $innerKey Inner relation key (for example user_id)
     * @param array|string|null  $outerKey Outer (parent) relation key (for example id = parent.id)
     */
    public function __construct(array $columns, $primaryKey, $innerKey, $outerKey)
    {
        parent::__construct($columns, $outerKey);
        $this->setDuplicateCriteria(...(array)$primaryKey);

        $this->innerKeys = (array)$innerKey;
    }

    /**
     * {@inheritdoc}
     */
    protected function push(array &$data): void
    {
        if ($this->parent === null) {
            throw new ParserException('Unable to register data tree, parent is missing.');
        }

        foreach ($this->innerKeys as $key) {
            if ($data[$key] === null) {
                // no data was parsed
                return;
            }
        }

        $this->parent->mountArray(
            $this->container,
            $this->outerKey,
            $this->intersectData($this->innerKeys, $data),
            $data
        );
    }
}
