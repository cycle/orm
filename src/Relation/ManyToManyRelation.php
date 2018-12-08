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
use Spiral\ORM\Command\CarrierInterface;
use Spiral\ORM\Command\CommandInterface;
use Spiral\ORM\Command\Control\Sequence;
use Spiral\ORM\Command\Database\Delete;
use Spiral\ORM\Command\Database\Insert;
use Spiral\ORM\Context\AcceptorInterface;
use Spiral\ORM\Iterator;
use Spiral\ORM\Loader\Relation\ManyToManyLoader;
use Spiral\ORM\Loader\RelationLoader;
use Spiral\ORM\Node\PivotedRootNode;
use Spiral\ORM\ORMInterface;
use Spiral\ORM\Point;
use Spiral\ORM\Relation;
use Spiral\ORM\Schema;
use Spiral\ORM\Util\Collection\CollectionPromise;
use Spiral\ORM\Util\ContextStorage;
use Spiral\ORM\Util\PivotedPromise;

class ManyToManyRelation extends AbstractRelation
{
    /** @var string */
    protected $thoughtInnerKey;

    /** @var string */
    protected $thoughtOuterKey;

    /**
     * @param ORMInterface $orm
     * @param string       $class
     * @param string       $relation
     * @param array        $schema
     */
    public function __construct(ORMInterface $orm, string $class, string $relation, array $schema)
    {
        parent::__construct($orm, $class, $relation, $schema);
        $this->thoughtInnerKey = $this->define(Relation::THOUGHT_INNER_KEY);
        $this->thoughtOuterKey = $this->define(Relation::THOUGHT_OUTER_KEY);
    }

    public function initPromise(Point $point): array
    {
        if (empty($innerKey = $this->fetchKey($point, $this->innerKey))) {
            return [null, null];
        }

        // todo: context promise (!)
        $pr = new PivotedPromise(
            [$this->outerKey => $innerKey],
            function () use ($innerKey) {
                // todo: store pivot context as well!!! or NOT?


                // repository won't work here

                // todo: need easy way to get access to table
                $tableName = $this->orm->getSchema()->define($this->class, Schema::TABLE);

                // todo: i need parent entity name
                $query = $this->orm->getDatabase($this->class)->select()->from($tableName);

                $loader = new ManyToManyLoader($this->orm, $this->class, $this->relation, $this->schema);

                $loader = $loader->withContext(
                    $loader,
                    [
                        'alias'      => $tableName,
                        'pivotAlias' => $tableName . '_pivot',
                        'method'     => RelationLoader::POSTLOAD
                    ]
                );

                /** @var ManyToManyLoader $loader */
                $query = $loader->configureQuery($query, [$innerKey]);

                $node = new PivotedRootNode(
                    $this->orm->getSchema()->define($this->class, Schema::COLUMNS),
                    $this->schema[Relation::PIVOT_COLUMNS],
                    $this->schema[Relation::OUTER_KEY],
                    $this->schema[Relation::THOUGHT_INNER_KEY],
                    $this->schema[Relation::THOUGHT_OUTER_KEY]
                );

                $iterator = $query->getIterator();
                foreach ($iterator as $row) {
                    $node->parseRow(0, $row);
                }
                $iterator->close();


                $elements = [];
                $pivotData = new \SplObjectStorage();
                foreach (new Iterator($this->orm, $this->class, $node->getResult()) as $pivot => $entity) {
                    $pivotData[$entity] = $pivot;
                    $elements[] = $entity;
                }

                return new ContextStorage($elements, $pivotData);
            }
        );

        return [new CollectionPromise($pr), $pr];
    }

    /**
     * @inheritdoc
     */
    public function init($data): array
    {
        $elements = [];
        $pivotData = new \SplObjectStorage();

        foreach (new Iterator($this->orm, $this->class, $data) as $pivot => $entity) {
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
            return $data->getPromise();
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
    public function queueRelation(
        CarrierInterface $parentCommand,
        $parentEntity,
        Point $parentState,
        $related,
        $original
    ): CommandInterface {
        $original = $original ?? new ContextStorage();

        if ($related instanceof PivotedPromise) {
            // todo: unify?
            $related = $related->__resolveContext();
        }

        if ($original instanceof PivotedPromise) {
            // todo: check consecutive changes
            $original = $original->__resolveContext();
            // todo: state->setRelation (!!!!!!)
            // YYEEAH?
        }

        $sequence = new Sequence();

        // link/sync new and existed elements
        foreach ($related->getElements() as $item) {
            $sequence->addCommand(
                $this->link($parentState, $item, $original->has($item))
            );
        }

        // un-link old elements
        foreach ($original->getElements() as $item) {
            if (!$related->has($item)) {
                $sequence->addCommand(
                    $this->unlink($parentState, $item)
                );
            }
        }

        return $sequence;
    }

    /**
     * Link two entities together and create/update pivot context.
     *
     * @param Point  $state
     * @param object $related
     * @param bool   $exists
     * @return CommandInterface
     */
    protected function link(Point $state, $related, $exists): CommandInterface
    {
        $relStore = $this->orm->queueStore($related);

        if ($exists) {
            // no changes in relation between the objects
            return $relStore;
        }

        $sync = new Insert($this->pivotDatabase(), $this->pivotTable());

        $sync->waitContext($this->thoughtInnerKey, true);
        $sync->waitContext($this->thoughtOuterKey, true);

        $state->pull($this->innerKey, $sync, $this->thoughtInnerKey, true);
        $this->getPoint($related)->pull($this->outerKey, $sync, $this->thoughtOuterKey, true);

        $sequence = new Sequence();
        $sequence->addCommand($relStore);
        $sequence->addCommand($sync);

        return $sequence;
    }

    /**
     * Remove the connection between two objects.
     *
     * @param Point  $state
     * @param object $related
     * @return CommandInterface
     */
    protected function unlink(Point $state, $related): CommandInterface
    {
        $relState = $this->getPoint($related);

        $delete = new Delete($this->pivotDatabase(), $this->pivotTable());
        $delete->waitScope($this->thoughtOuterKey);
        $delete->waitScope($this->thoughtInnerKey);

        $state->pull($this->innerKey, $delete, $this->thoughtInnerKey, true, AcceptorInterface::SCOPE);
        $relState->pull($this->outerKey, $delete, $this->thoughtOuterKey, true, AcceptorInterface::SCOPE);

        return $delete;
    }

    /**
     * @return DatabaseInterface
     */
    protected function pivotDatabase(): DatabaseInterface
    {
        return $this->orm->getDatabase($this->class);
    }

    /**
     * @return string
     */
    protected function pivotTable(): string
    {
        return $this->define(Relation::PIVOT_TABLE);
    }
}