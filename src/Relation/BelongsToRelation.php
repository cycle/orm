<?php
declare(strict_types=1);
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

namespace Spiral\Cycle\Relation;

use Spiral\Cycle\Command\Branch\Nil;
use Spiral\Cycle\Command\CommandInterface;
use Spiral\Cycle\Command\ContextCarrierInterface as CC;
use Spiral\Cycle\Exception\Relation\NullException;
use Spiral\Cycle\Heap\Node;
use Spiral\Cycle\Relation\Traits\PromiseOneTrait;

/**
 * Provides ability to link to the parent object. Will claim branch up to the parent object and it's relations. To disable
 * branch walk-though use RefersTo relation.
 */
class BelongsToRelation extends AbstractRelation implements DependencyInterface
{
    use PromiseOneTrait;

    /**
     * @inheritdoc
     */
    public function queue(CC $parentStore, $parentEntity, Node $parentNode, $related, $original): CommandInterface
    {
        if (is_null($related)) {
            if ($this->isRequired()) {
                throw new NullException("Relation {$this} can not be null");
            }

            if (!is_null($original)) {
                // reset the key
                $parentStore->register($this->innerKey, null, true);
            }

            // nothing to do
            return new Nil();
        }

        $relStore = $this->orm->queueStore($related);
        $relNode = $this->getNode($related);
        $this->assertValid($related, $relNode);

        $this->forwardContext($relNode, $this->outerKey, $parentStore, $parentNode, $this->innerKey);

        return $relStore;
    }
}
