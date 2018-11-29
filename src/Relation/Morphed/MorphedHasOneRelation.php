<?php
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

namespace Spiral\ORM\Relation\Morphed;

use Spiral\ORM\Command\CommandInterface;
use Spiral\ORM\Command\ContextualInterface;
use Spiral\ORM\ORMInterface;
use Spiral\ORM\Relation;
use Spiral\ORM\Relation\HasOneRelation;
use Spiral\ORM\Schema;
use Spiral\ORM\State;
use Spiral\ORM\Util\Promise;

/**
 * Inverted version of belongs to morphed.
 */
class MorphedHasOneRelation extends HasOneRelation
{
    /** @var mixed|null */
    private $morphKey;

    /**
     * @param ORMInterface $orm
     * @param string       $class
     * @param string       $relation
     * @param array        $schema
     */
    public function __construct(ORMInterface $orm, string $class, string $relation, array $schema)
    {
        parent::__construct($orm, $class, $relation, $schema);
        $this->morphKey = $this->define(Relation::MORPH_KEY);
    }

    public function initPromise(State $state, $data): array
    {
        // todo: here we need paths (!)
        if (empty($innerKey = $this->fetchKey($state, $this->innerKey))) {
            return [null, null];
        }

        // todo: state get Alias

        $pr = new Promise(
            [
                $this->outerKey => $innerKey,
                $this->morphKey => $state->getAlias()
            ],
            function ($context) use ($state) {
                // todo: check in map
                return $this->orm->getMapper($this->class)->getRepository()->findOne($context);
            }
        );

        return [$pr, $pr];
    }

    /**
     * @inheritdoc
     */
    public function queueRelation(
        ContextualInterface $parent,
        $entity,
        State $state,
        $related,
        $original
    ): CommandInterface {
        $store = parent::queueRelation($parent, $entity, $state, $related, $original);
        $parentAlias = $this->orm->getSchema()->define(get_class($entity), Schema::ALIAS);

        if ($store instanceof ContextualInterface) {
            // todo: work it out
            if (!is_null($related)) {
                if ($this->fetchKey($this->getState($related), $this->morphKey) != $parentAlias) {
                    $store->setContext($this->morphKey, $parentAlias);
                }
            }
        }

        return $store;
    }
}