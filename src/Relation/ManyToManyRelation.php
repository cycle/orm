<?php
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

namespace Spiral\ORM\Relation;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Spiral\Database\DatabaseInterface;
use Spiral\ORM\Command\Branch\Sequence;
use Spiral\ORM\Command\CommandInterface;
use Spiral\ORM\Command\ContextCarrierInterface as CC;
use Spiral\ORM\Command\Database\Delete;
use Spiral\ORM\Command\Database\Insert;
use Spiral\ORM\Context\ConsumerInterface;
use Spiral\ORM\Iterator;
use Spiral\ORM\Node;
use Spiral\ORM\ORMInterface;
use Spiral\ORM\Relation;
use Spiral\ORM\Relation\ManyToMany\PivotedPromise;
use Spiral\ORM\Util\Collection\CollectionPromise;
use Spiral\ORM\Util\ContextStorage;
use Spiral\ORM\Util\Promise\PivotedPromiseInterface;

class ManyToManyRelation extends AbstractRelation
{
    /** @var string */
    protected $thoughtInnerKey;

    /** @var string */
    protected $thoughtOuterKey;

    /**
     * @param ORMInterface $orm
     * @param string       $target
     * @param string       $name
     * @param array        $schema
     */
    public function __construct(ORMInterface $orm, string $name, string $target, array $schema)
    {
        parent::__construct($orm, $name, $target, $schema);
        $this->thoughtInnerKey = $this->schema[Relation::THOUGHT_INNER_KEY] ?? null;
        $this->thoughtOuterKey = $this->schema[Relation::THOUGHT_OUTER_KEY] ?? null;
    }

    /**
     * @inheritdoc
     */
    public function initPromise(Node $parentNode): array
    {
        if (empty($innerKey = $this->fetchKey($parentNode, $this->innerKey))) {
            return [new ArrayCollection(), null];
        }

        // will take care of all the loading and scoping
        $p = new PivotedPromise($this->orm, $this->target, $this->schema, $innerKey);

        return [new CollectionPromise($p), $p];
    }

    /**
     * @inheritdoc
     */
    public function init(array $data): array
    {
        $elements = [];
        $pivotData = new \SplObjectStorage();

        foreach (new Iterator($this->orm, $this->target, $data) as $pivot => $entity) {
            $pivotData[$entity] = $pivot;
            $elements[] = $entity;
        }

        return [new ArrayCollection($elements), new ContextStorage($elements, $pivotData)];
    }

    /**
     * @inheritdoc
     */
    public function extract($data)
    {
        if ($data instanceof CollectionPromise && !$data->isInitialized()) {
            return $data->toPromise();
        }

        if ($data instanceof Collection) {
            return new ContextStorage($data->toArray());
        }

        return new ContextStorage();
    }

    /**
     * @inheritdoc
     *
     * @param ContextStorage $related
     * @param ContextStorage $original
     */
    public function queue(CC $parentStore, $parentEntity, Node $parentNode, $related, $original): CommandInterface
    {
        $original = $original ?? new ContextStorage();

        if ($related instanceof PivotedPromiseInterface) {
            $related = $related->__resolveContext();
        }

        if ($original instanceof PivotedPromiseInterface) {
            $original = $original->__resolveContext();
        }

        $sequence = new Sequence();

        // link/sync new and existed elements
        foreach ($related->getElements() as $item) {
            $sequence->addCommand($this->link($parentNode, $item, $original->has($item)));
        }

        // un-link old elements
        foreach ($original->getElements() as $item) {
            if (!$related->has($item)) {
                $sequence->addCommand($this->unlink($parentNode, $item));
            }
        }

        return $sequence;
    }

    /**
     * Link two entities together and create/update pivot context.
     *
     * @param Node   $node
     * @param object $related
     * @param bool   $exists
     * @return CommandInterface
     */
    protected function link(Node $node, $related, $exists): CommandInterface
    {
        $relStore = $this->orm->queueStore($related);

        if ($exists) {
            // no changes in relation between the objects
            return $relStore;
        }

        $sync = new Insert($this->pivotDatabase(), $this->pivotTable());

        $sync->waitContext($this->thoughtInnerKey, true);
        $sync->waitContext($this->thoughtOuterKey, true);

        $node->forward($this->innerKey, $sync, $this->thoughtInnerKey, true);
        $this->getNode($related)->forward($this->outerKey, $sync, $this->thoughtOuterKey, true);

        $sequence = new Sequence();
        $sequence->addCommand($relStore);
        $sequence->addCommand($sync);

        return $sequence;
    }

    /**
     * Remove the connection between two objects.
     *
     * @param Node   $node
     * @param object $related
     * @return CommandInterface
     */
    protected function unlink(Node $node, $related): CommandInterface
    {
        $relNode = $this->getNode($related);

        $delete = new Delete($this->pivotDatabase(), $this->pivotTable());
        $delete->waitScope($this->thoughtOuterKey);
        $delete->waitScope($this->thoughtInnerKey);

        $node->forward(
            $this->innerKey
            , $delete,
            $this->thoughtInnerKey,
            true,
            ConsumerInterface::SCOPE
        );

        $relNode->forward(
            $this->outerKey,
            $delete,
            $this->thoughtOuterKey,
            true,
            ConsumerInterface::SCOPE
        );

        return $delete;
    }

    /**
     * @return DatabaseInterface
     */
    protected function pivotDatabase(): DatabaseInterface
    {
        return $this->getMapper()->getDatabase();
    }

    /**
     * @return string
     */
    protected function pivotTable(): string
    {
        return $this->schema[Relation::PIVOT_TABLE] ?? null;
    }
}