<?php

declare(strict_types=1);

namespace Cycle\ORM\Parser;

use Cycle\ORM\Exception\ParserException;
use Cycle\ORM\Parser\Traits\DuplicateTrait;
use Throwable;

/**
 * Represents data node in a tree with ability to parse line of results, split it into sub
 * relations, aggregate reference keys and etc.
 *
 * Nodes can be used as to parse one big and flat query, or when multiple queries provide their
 * data into one dataset, in both cases flow is identical from standpoint of Nodes (but offsets are
 * different).
 *
 * @internal
 */
abstract class AbstractNode
{
    use DuplicateTrait;

    // Indicates tha data must be placed at the last registered reference
    protected const LAST_REFERENCE = ['~'];

    /**
     * Indicates that node data is joined to parent row and must receive part of incoming row
     * subset.
     *
     */
    protected bool $joined = false;

    /**
     * List of columns node must fetch from the row.
     *
     */
    protected array $columns = [];

    /**
     * Declared column list which must be aggregated in a parent node. i.e. Parent Key
     * @var string[]
     */
    protected array $outerKeys;

    /**
     * Node location in a tree. Set when node is registered.
     *
     * @internal
     */
    protected ?string $container = null;

    /** @internal */
    protected ?self $parent = null;

    /** @internal */
    protected ?TypecastInterface $typecast = null;

    /** @var array<string, AbstractNode> */
    protected array $nodes = [];

    protected ?AbstractNode $merge = null;

    protected ?string $indexName = null;

    /**
     * Indexed keys and values associated with references
     *
     * @internal
     */
    protected ?MultiKeyCollection $indexedData = null;

    /**
     * @param array $columns  When columns are empty original line will be returned as result.
     * @param array|null $outerKeys Defines column name in parent Node to be aggregated.
     */
    public function __construct(array $columns, array $outerKeys = null)
    {
        $this->columns = $columns;
        $this->indexName = $outerKeys === null ? null : implode(':', $outerKeys);
        $this->outerKeys = $outerKeys ?? [];
        $this->indexedData = new MultiKeyCollection();
    }

    public function __destruct()
    {
        $this->parent = null;
        $this->nodes = [];
        $this->indexedData = null;
        $this->duplicates = [];
    }

    /**
     * @param TypecastInterface $typecast
     */
    public function setTypecast(TypecastInterface $typecast): void
    {
        $this->typecast = $typecast;
    }

    /**
     * Parse given row of data and populate reference tree.
     *
     * @return int Must return number of parsed columns.
     */
    public function parseRow(int $offset, array $row): int
    {
        $data = $this->fetchData($offset, $row);

        if ($this->deduplicate($data)) {
            foreach ($this->indexedData->getIndexes() as $index) {
                try {
                    $this->indexedData->addItem($index, $data);
                } catch (Throwable $e) {
                }
            }

            //Let's force placeholders for every sub loaded
            foreach ($this->nodes as $name => $node) {
                if ($node instanceof MergeNode) {
                    continue;
                }
                $data[$name] = $node instanceof ArrayNode ? [] : null;
            }

            $this->push($data);
        } elseif ($this->parent !== null) {
            // register duplicate rows in each parent row
            $this->push($data);
        }

        $innerOffset = 0;
        $iterate = $this->merge === null ? $this->nodes : [$this->merge] + $this->nodes;
        foreach ($iterate as $node) {
            if (!$node->joined) {
                continue;
            }

            /**
             * We are looking into branch like structure:
             * node
             *  - node
             *      - node
             *      - node
             * node
             *
             * This means offset has to be calculated using all nested nodes
             */
            $innerColumns = $node->parseRow(count($this->columns) + $offset, $row);

            //Counting next selection offset
            $offset += $innerColumns;

            //Counting nested tree offset
            $innerOffset += $innerColumns;
        }

        return count($this->columns) + $innerOffset;
    }

    /**
     * Get list of reference key values aggregated by parent.
     *
     * @throws ParserException
     */
    public function getReferenceValues(): array
    {
        if ($this->parent === null) {
            throw new ParserException('Unable to aggregate reference values, parent is missing.');
        }
        if (!$this->parent->indexedData->hasIndex($this->indexName)) {
            return [];
        }

        return $this->parent->indexedData->getCriteria($this->indexName, true);
    }

    /**
     * Register new node into NodeTree. Nodes used to convert flat results into tree representation
     * using reference aggregations. Node would not be used to parse incoming row results.
     *
     * @throws ParserException
     */
    public function linkNode(?string $container, AbstractNode $node): void
    {
        $node->parent = $this;
        if ($container !== null) {
            $this->nodes[$container] = $node;
            $node->container = $container;
        } else {
            $this->merge = $node;
        }

        if ($node->indexName !== null) {
            foreach ($node->outerKeys as $key) {
            // foreach ($node->indexValues->getIndex($this->indexName) as $key) {
                if (!in_array($key, $this->columns, true)) {
                    throw new ParserException("Unable to create reference, key `{$key}` does not exist.");
                }
            }
            if (!$this->indexedData->hasIndex($node->indexName)) {
                $this->indexedData->createIndex($node->indexName, $node->outerKeys);
            }
        }
    }

    /**
     * Register new node into NodeTree. Nodes used to convert flat results into tree representation
     * using reference aggregations. Node will used to parse row results.
     *
     * @throws ParserException
     */
    public function joinNode(?string $container, AbstractNode $node): void
    {
        $node->joined = true;
        $this->linkNode($container, $node);
    }

    /**
     * Fetch sub node.
     *
     * @throws ParserException
     */
    public function getNode(string $container): AbstractNode
    {
        if (!isset($this->nodes[$container])) {
            throw new ParserException("Undefined node `{$container}`.");
        }

        return $this->nodes[$container];
    }

    public function getMergeNode(): self
    {
        return $this->merge;
    }

    public function mergeInheritanceNode(): void
    {
        if ($this->merge === null) {
            return;
        }
        $this->merge->mergeInheritanceNode();
    }

    /**
     * Mount record data into internal data storage under specified container using reference key
     * (inner key) and reference criteria (outer key value).
     *
     * Example (default ORM Loaders):
     * $this->parent->mount('profile', 'id', 1, [
     *      'id' => 100,
     *      'user_id' => 1,
     *      ...
     * ]);
     *
     * In this example "id" argument is inner key of "user" record and it's linked to outer key
     * "user_id" in "profile" record, which defines reference criteria as 1.
     *
     * Attention, data WILL be referenced to new memory location!
     *
     * @throws ParserException
     */
    protected function mount(string $container, string $index, array $criteria, array &$data): void
    {
        if ($criteria === self::LAST_REFERENCE) {
            if (!$this->indexedData->hasIndex($index)) {
                return;
            }
            $criteria = $this->indexedData->getLastItemKeys($index);
        }

        if ($this->indexedData->getItemsCount($index, $criteria) === 0) {
            throw new ParserException(sprintf('Undefined reference `%s` "%s".', $index, implode(':', $criteria)));
        }

        foreach ($this->indexedData->getItemsSubset($index, $criteria) as &$subset) {
            if (isset($subset[$container])) {
                // back reference!
                $data = &$subset[$container];
            } else {
                $subset[$container] = &$data;
            }

            unset($subset);
        }
    }

    /**
     * Mount record data into internal data storage under specified container using reference key
     * (inner key) and reference criteria (outer key value).
     *
     * Example (default ORM Loaders):
     * $this->parent->mountArray('comments', 'id', 1, [
     *      'id' => 100,
     *      'user_id' => 1,
     *      ...
     * ]);
     *
     * In this example "id" argument is inner key of "user" record and it's linked to outer key
     * "user_id" in "profile" record, which defines reference criteria as 1.
     *
     * Add added records will be added as array items.
     *
     * @throws ParserException
     */
    protected function mountArray(string $container, string $index, mixed $criteria, array &$data): void
    {
        if (!$this->indexedData->hasIndex($index)) {
            throw new ParserException("Undefined index `{$index}`.");
        }

        foreach ($this->indexedData->getItemsSubset($index, $criteria) as &$subset) {
            if (!in_array($data, $subset[$container], true)) {
                $subset[$container][] = &$data;
            }
        }
        unset($subset);
    }

    /**
     * @throws ParserException
     */
    protected function mergeData(string $index, array $criteria, array $data, bool $overwrite): void
    {
        if ($criteria === self::LAST_REFERENCE) {
            if (!$this->indexedData->hasIndex($index)) {
                return;
            }
            $criteria = $this->indexedData->getLastItemKeys($index);
        }

        if ($this->indexedData->getItemsCount($index, $criteria) === 0) {
            throw new ParserException(sprintf('Undefined reference `%s` "%s".', $index, implode(':', $criteria)));
        }

        foreach ($this->indexedData->getItemsSubset($index, $criteria) as &$subset) {
            $subset = $overwrite ? array_merge($subset, $data) : array_merge($data, $subset);
            unset($subset);
        }
    }

    /**
     * Register data result.
     */
    abstract protected function push(array &$data);

    /**
     * Fetch record columns from query row, must use data offset to slice required part of query.
     */
    protected function fetchData(int $dataOffset, array $line): array
    {
        try {
            //Combine column names with sliced piece of row
            $result = array_combine(
                $this->columns,
                array_slice($line, $dataOffset, count($this->columns))
            );

            if ($this->typecast !== null) {
                return $this->typecast->cast($result);
            }

            return $result;
        } catch (Throwable $e) {
            throw new ParserException(
                'Unable to parse incoming row: ' . $e->getMessage(),
                $e->getCode(),
                $e
            );
        }
    }

    protected function intersectData(array $keys, array $data): array
    {
        $result = [];
        foreach ($keys as $key) {
            $result[$key] = $data[$key];
        }
        return $result;
    }
}
