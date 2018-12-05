<?php
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

namespace Spiral\ORM\Relation\Morphed;

use Spiral\ORM\Command\CommandInterface;
use Spiral\ORM\Command\CarrierInterface;
use Spiral\ORM\ORMInterface;
use Spiral\ORM\Relation;
use Spiral\ORM\Relation\HasOneRelation;
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
        if (empty($innerKey = $this->fetchKey($state, $this->innerKey))) {
            return [null, null];
        }

        // todo: need simple promise :)

        // todo: better promises?
        $promise = new Promise(
            [
                $this->outerKey => $innerKey,
                $this->morphKey => $state->getAlias()
            ],
            function ($context) {
                // todo: check in map
                return $this->orm->getMapper($this->class)->getRepository()->findOne($context);
            }
        );

        return [$promise, $promise];
    }

    /**
     * @inheritdoc
     */
    public function queueRelation(
        CarrierInterface $parentCommand,
        $entity,
        State $state,
        $related,
        $original
    ): CommandInterface {
        $store = parent::queueRelation($parentCommand, $entity, $state, $related, $original);

        if ($store instanceof CarrierInterface && !is_null($related)) {
            $relState = $this->getState($related);
            if ($this->fetchKey($relState, $this->morphKey) != $state->getAlias()) {
                $store->setContext($this->morphKey, $state->getAlias());
                $relState->setData([$this->morphKey => $state->getAlias()]);
            }
        }

        return $store;
    }
}