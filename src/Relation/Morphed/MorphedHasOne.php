<?php declare(strict_types=1);
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

namespace Cycle\ORM\Relation\Morphed;

use Cycle\ORM\Command\CommandInterface;
use Cycle\ORM\Command\ContextCarrierInterface as CC;
use Cycle\ORM\Exception\RelationException;
use Cycle\ORM\Heap\Node;
use Cycle\ORM\ORMInterface;
use Cycle\ORM\Relation;
use Cycle\ORM\Relation\HasOne;

/**
 * Inverted version of belongs to morphed.
 */
class MorphedHasOne extends HasOne
{
    /** @var string */
    private $morphKey;

    /**
     * @param ORMInterface $orm
     * @param string       $target
     * @param string       $name
     * @param array        $schema
     */
    public function __construct(ORMInterface $orm, string $name, string $target, array $schema)
    {
        parent::__construct($orm, $name, $target, $schema);
        $this->morphKey = $schema[Relation::MORPH_KEY];
    }

    /**
     * @inheritdoc
     */
    public function initPromise(Node $parentNode): array
    {
        if (is_null($innerKey = $this->fetchKey($parentNode, $this->innerKey))) {
            return [null, null];
        }

        $scope = [
            $this->outerKey => $innerKey,
            $this->morphKey => $parentNode->getRole()
        ];

        $r = $this->orm->promise($this->target, $scope);

        return [$r, $r];
    }

    /**
     * @inheritdoc
     */
    public function queue(CC $parentStore, $parentEntity, Node $parentNode, $related, $original): CommandInterface
    {
        $relStore = parent::queue($parentStore, $parentEntity, $parentNode, $related, $original);

        if ($relStore instanceof CC && !is_null($related)) {
            $relNode = $this->getNode($related);

            if ($this->fetchKey($relNode, $this->morphKey) != $parentNode->getRole()) {
                $relStore->register($this->morphKey, $parentNode->getRole(), true);
                $relNode->register($this->morphKey, $parentNode->getRole(), true);
            }
        }

        return $relStore;
    }

    /**
     * Assert that given entity is allowed for the relation.
     *
     * @param Node $relNode
     *
     * @throws RelationException
     */
    protected function assertValid(Node $relNode)
    {
        // no need to validate morphed relation yet
    }
}