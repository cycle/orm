<?php

declare(strict_types=1);

namespace Cycle\ORM\Relation\Pivoted;

use Cycle\ORM\Exception\ORMException;
use Cycle\ORM\Heap\Node;
use Cycle\ORM\Iterator;
use Cycle\ORM\ORMInterface;
use Cycle\ORM\Parser\RootNode;
use Cycle\ORM\Promise\PromiseInterface;
use Cycle\ORM\Relation;
use Cycle\ORM\Select;
use Cycle\ORM\Select\JoinableLoader;
use Cycle\ORM\Select\Loader\ManyToManyLoader;
use IteratorAggregate;

/**
 * Promise use loader to configure query and it's scope.
 */
final class PivotedPromise implements PromiseInterface, IteratorAggregate
{
    /** @internal */
    private ?ORMInterface $orm;

    private string $target;

    /** @internal */
    private array $relationSchema;

    private array $innerKeys;

    private ?PivotedStorage $resolved = null;

    public function __construct(ORMInterface $orm, string $target, array $relationSchema, array $innerKeys)
    {
        $this->orm = $orm;
        $this->target = $target;
        $this->relationSchema = $relationSchema;
        $this->innerKeys = $innerKeys;
    }

    public function __loaded(): bool
    {
        return $this->orm === null;
    }

    public function __role(): string
    {
        return $this->target;
    }

    public function __scope(): array
    {
        return array_combine($this->relationSchema[Relation::INNER_KEY], $this->innerKeys);
    }

    public function __resolve(): PivotedStorage
    {
        /*
         * This method emulates the selection of MtM nodes by skipping parent relation (as it usually done
         * in query) and injecting parent ID instead.
         */

        if ($this->orm === null) {
            return $this->resolved;
        }

        if (!$this->orm instanceof Select\SourceProviderInterface) {
            throw new ORMException('PivotedPromise require ORM to implement SourceFactoryInterface');
        }

        // getting scoped query
        $query = (new Select\RootLoader($this->orm, $this->target))->buildQuery();

        // responsible for all the scoping
        $loader = new ManyToManyLoader(
            $this->orm,
            $this->orm->getSource($this->target)->getTable(),
            $this->target,
            $this->relationSchema
        );

        /** @var ManyToManyLoader $loader */
        $loader = $loader->withContext($loader, [
            'constrain' => $this->orm->getSource($this->target)->getConstrain(),
            'as'        => $this->target,
            'method'    => JoinableLoader::POSTLOAD
        ]);

        $query = $loader->configureQuery($query, [$this->innerKeys]);

        // we are going to add pivot node into virtual root node (only ID) to aggregate the results
        $root = new RootNode(
            (array)$this->relationSchema[Relation::INNER_KEY],
            (array)$this->relationSchema[Relation::INNER_KEY]
        );

        $node = $loader->createNode();
        $root->linkNode('output', $node);

        // emulate presence of parent entity
        $root->parseRow(0, $this->innerKeys);

        $iterator = $query->getIterator();
        foreach ($iterator as $row) {
            $node->parseRow(0, $row);
        }
        $iterator->close();

        // load all eager relations, forbid loader to re-fetch data (make it think it was joined)
        $loader->withContext($loader, ['method' => JoinableLoader::INLOAD])->loadData($node);

        $elements = [];
        $pivotData = new \SplObjectStorage();
        foreach (new Iterator($this->orm, $this->target, $root->getResult()[0]['output']) as $pivot => $entity) {
            $pivotData[$entity] = $this->orm->make(
                $this->relationSchema[Relation::THROUGH_ENTITY],
                $pivot,
                Node::MANAGED
            );

            $elements[] = $entity;
        }

        $this->resolved = new PivotedStorage($elements, $pivotData);
        $this->orm = null;

        return $this->resolved;
    }

    public function getIterator()
    {
        return $this->__resolve();
    }
}
