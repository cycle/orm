<?php
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

namespace Spiral\Cycle\Select\Traits;

use Spiral\Cycle\Exception\LoaderException;
use Spiral\Cycle\Heap\Node;
use Spiral\Cycle\Relation;

trait MorphedTrait
{
    /**
     * Create condition to point relation to the given outerKey or entity node.
     *
     * @param mixed|Node $node
     * @return array
     */
    protected function makeConstrain($node): array
    {
        $innerKey = $this->localKey($this->define(Relation::INNER_KEY));
        $outerKey = $this->define(Relation::OUTER_KEY);
        $morphKey = $this->localKey(Relation::MORPH_KEY);

        if (!$node instanceof Node) {
            throw new LoaderException("Unable to point {$this} to non entity");
        }

        if (!array_key_exists($outerKey, $node->getData())) {
            throw new LoaderException("Unable to point {$this} to `{$node->getRole()}`, outerKey value not found");
        }

        return [$innerKey => $node->getData()[$outerKey], $morphKey => $node->getRole()];
    }
}