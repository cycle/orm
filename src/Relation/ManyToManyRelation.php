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
use Spiral\ORM\Command\ContextCarrierInterface;
use Spiral\ORM\Command\Database\Delete;
use Spiral\ORM\Command\Database\Insert;
use Spiral\ORM\Context\ConsumerInterface;
use Spiral\ORM\Iterator;
use Spiral\ORM\Loader\JoinableLoader;
use Spiral\ORM\Loader\Relation\ManyToManyLoader;
use Spiral\ORM\Node;
use Spiral\ORM\ORMInterface;
use Spiral\ORM\Relation;
use Spiral\ORM\Schema;
use Spiral\ORM\TreeGenerator\PivotedRootNode;
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

    public function initPromise(Node $point): array
    {
        if (empty($innerKey = $this->fetchKey($point, $this->innerKey))) {
            return [null, null];
        }

        // todo: context promise (!)
        $pr = new PivotedPromise(
            $this->target,
            [$this->outerKey => $innerKey],
            function () use ($innerKey) {
                // todo: store pivot context as well!!! or NOT?


                // repository won't work here

                // todo: need easy way to get access to table
                $tableName = $this->orm->getSchema()->define($this->target, Schema::TABLE);

                // todo: i need parent entity name
                $query = $this->orm->getDatabase($this->target)->select()->from($tableName);

                $loader = new ManyToManyLoader($this->orm, $this->target, $this->name, $this->schema);

                $loader = $loader->withContext(
                    $loader,
                    [
                        'alias'      => $tableName,
                        'pivotAlias' => $tableName . '_pivot',
                        'method'     => JoinableLoader::POSTLOAD
                    ]
                );

                /** @var ManyToManyLoader $loader */
                $query = $loader->configureQuery($query, [$innerKey]);

                $node = new PivotedRootNode(
                    $this->orm->getSchema()->define($this->target, Schema::COLUMNS),
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
                foreach (new Iterator($this->orm, $this->target, $node->getResult()) as $pivot => $entity) {
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
    public function queue(
        ContextCarrierInterface $parentStore,
        $parentEntity,
        Node $parentNode,
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
                $this->link($parentNode, $item, $original->has($item))
            );
        }

        // un-link old elements
        foreach ($original->getElements() as $item) {
            if (!$related->has($item)) {
                $sequence->addCommand(
                    $this->unlink($parentNode, $item)
                );
            }
        }

        return $sequence;
    }

    /**
     * Link two entities together and create/update pivot context.
     *
     * @param Node   $state
     * @param object $related
     * @param bool   $exists
     * @return CommandInterface
     */
    protected function link(Node $state, $related, $exists): CommandInterface
    {
        $relStore = $this->orm->queueStore($related);

        if ($exists) {
            // no changes in relation between the objects
            return $relStore;
        }

        $sync = new Insert($this->pivotDatabase(), $this->pivotTable());

        $sync->waitContext($this->thoughtInnerKey, true);
        $sync->waitContext($this->thoughtOuterKey, true);

        $state->listen($this->innerKey, $sync, $this->thoughtInnerKey, true);
        $this->getNode($related)->listen($this->outerKey, $sync, $this->thoughtOuterKey, true);

        $sequence = new Sequence();
        $sequence->addCommand($relStore);
        $sequence->addCommand($sync);

        return $sequence;
    }

    /**
     * Remove the connection between two objects.
     *
     * @param Node   $state
     * @param object $related
     * @return CommandInterface
     */
    protected function unlink(Node $state, $related): CommandInterface
    {
        $relState = $this->getNode($related);

        $delete = new Delete($this->pivotDatabase(), $this->pivotTable());
        $delete->waitScope($this->thoughtOuterKey);
        $delete->waitScope($this->thoughtInnerKey);

        $state->listen($this->innerKey, $delete, $this->thoughtInnerKey, true, ConsumerInterface::SCOPE);
        $relState->listen($this->outerKey, $delete, $this->thoughtOuterKey, true, ConsumerInterface::SCOPE);

        return $delete;
    }

    /**
     * @return DatabaseInterface
     */
    protected function pivotDatabase(): DatabaseInterface
    {
        return $this->orm->getDatabase($this->target);
    }

    /**
     * @return string
     */
    protected function pivotTable(): string
    {
        return $this->schema[Relation::PIVOT_TABLE] ?? null;
    }
}