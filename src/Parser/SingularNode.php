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
 * Node with ability to push it's data into referenced tree location.
 *
 * @internal
 */
final class SingularNode extends AbstractNode
{
    /** @var string */
    protected $innerKey;
    /** @var string[] */
    protected $innerKeys;

    /**
     * @param array              $columns
     * @param string|array       $primaryKey
     * @param string|array       $innerKey Inner relation key (for example user_id)
     * @param string|array|null  $outerKey Outer (parent) relation key (for example id = parent.id)
     */
    public function __construct(array $columns, $primaryKey, $innerKey, $outerKey)
    {
        parent::__construct($columns, $outerKey);
        $this->setDuplicateCriteria(...(array)$primaryKey);

        $this->innerKey = implode(':', $innerKey);
        $this->innerKeys = $innerKey;
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
                //No data was loaded
                return;
            }
        }

        $this->parent->mount(
            $this->container,
            $this->outerKey,
            $this->intersectData($this->innerKeys, $data),
            $data
        );
    }
}
