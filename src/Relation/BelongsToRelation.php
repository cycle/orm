<?php
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

namespace Spiral\ORM\Relation;

use Spiral\ORM\Command\Branch\Nil;
use Spiral\ORM\Command\CommandInterface;
use Spiral\ORM\Command\ContextCarrierInterface as CC;
use Spiral\ORM\DependencyInterface;
use Spiral\ORM\Exception\Relation\NullException;
use Spiral\ORM\Node;
use Spiral\ORM\Relation\Traits\PromiseOneTrait;

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

        $this->forwardContext(
            $this->getNode($related, +1),
            $this->outerKey,
            $parentStore,
            $parentNode,
            $this->innerKey
        );

        return $relStore;
    }
}