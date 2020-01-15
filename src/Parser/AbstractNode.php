<?php

/**
 * Cycle DataMapper ORM
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

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
     * @var bool
     */
    protected $joined = false;

    /**
     * List of columns node must fetch from the row.
     *
     * @var array
     */
    protected $columns = [];

    /**
     * Declared column which must be aggregated in a parent node. i.e. Parent Key
     *
     * @var null|string
     */
    protected $outerKey;

    /**
     * Node location in a tree. Set when node is registered.
     *
     * @internal
     * @var string
     */
    protected $container;

    /**
     * @internal
     * @var AbstractNode
     */
    protected $parent;

    /**
     * @internal
     * @var TypecastInterface|null
     */
    protected $typecast;

    /** @var AbstractNode[] */
    protected $nodes = [];

    /**
     * Tree parts associated with reference keys and key values:
     * $this->collectedReferences[id][ID_VALUE] = [ITEM1, ITEM2, ...].
     *
     * @internal
     * @var array
     */
    protected $references = [];

    /**
     * Set of keys to be aggregated by Parser while parsing results.
     *
     * @internal
     * @var array
     */
    protected $trackReferences = [];

    /**
     * @param array       $columns When columns are empty original line will be returned as result.
     * @param string|null $outerKey Defines column name in parent Node to be aggregated.
     */
    public function __construct(array $columns, string $outerKey = null)
    {
        $this->columns = $columns;
        $this->outerKey = $outerKey;
    }

    /**
     * Destructing.
     */
    public function __destruct()
    {
        $this->parent = null;
        $this->nodes = [];
        $this->references = [];
        $this->trackReferences = [];
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
     * @param int   $offset
     * @param array $row
     * @return int Must return number of parsed columns.
     */
    public function parseRow(int $offset, array $row): int
    {
        $data = $this->fetchData($offset, $row);

        if ($this->deduplicate($data)) {
            foreach ($this->trackReferences as $key) {
                if (!empty($data[$key])) {
                    $this->references[$key][$data[$key]][] = &$data;
                }
            }

            //Let's force placeholders for every sub loaded
            foreach ($this->nodes as $name => $node) {
                $data[$name] = $node instanceof ArrayNode ? [] : null;
            }

            $this->push($data);
        } elseif ($this->parent !== null) {
            // register duplicate rows in each parent row
            $this->push($data);
        }

        $innerOffset = 0;
        foreach ($this->nodes as $container => $node) {
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
     * @return array
     *
     * @throws ParserException
     */
    public function getReferences(): array
    {
        if ($this->parent === null) {
            throw new ParserException('Unable to aggregate reference values, parent is missing');
        }

        if (empty($this->parent->references[$this->outerKey])) {
            return [];
        }

        return array_keys($this->parent->references[$this->outerKey]);
    }

    /**
     * Register new node into NodeTree. Nodes used to convert flat results into tree representation
     * using reference aggregations. Node would not be used to parse incoming row results.
     *
     * @param string       $container
     * @param AbstractNode $node
     *
     * @throws ParserException
     */
    public function linkNode(string $container, AbstractNode $node): void
    {
        $this->nodes[$container] = $node;
        $node->container = $container;
        $node->parent = $this;

        if ($node->outerKey !== null) {
            if (!in_array($node->outerKey, $this->columns, true)) {
                throw new ParserException(
                    "Unable to create reference, key `{$node->outerKey}` does not exist"
                );
            }

            if (!in_array($node->outerKey, $this->trackReferences, true)) {
                $this->trackReferences[] = $node->outerKey;
            }
        }
    }

    /**
     * Register new node into NodeTree. Nodes used to convert flat results into tree representation
     * using reference aggregations. Node will used to parse row results.
     *
     * @param string       $container
     * @param AbstractNode $node
     *
     * @throws ParserException
     */
    public function joinNode(string $container, AbstractNode $node): void
    {
        $node->joined = true;
        $this->linkNode($container, $node);
    }

    /**
     * Fetch sub node.
     *
     * @param string $container
     * @return AbstractNode
     *
     * @throws ParserException
     */
    public function getNode(string $container): AbstractNode
    {
        if (!isset($this->nodes[$container])) {
            throw new ParserException("Undefined node `{$container}`");
        }

        return $this->nodes[$container];
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
     * @param string $container
     * @param string $key
     * @param mixed  $criteria
     * @param array  $data
     *
     * @throws ParserException
     */
    protected function mount(string $container, string $key, $criteria, array &$data): void
    {
        if ($criteria === self::LAST_REFERENCE) {
            end($this->references[$key]);
            $criteria = key($this->references[$key]);
        }

        if (!array_key_exists($criteria, $this->references[$key])) {
            throw new ParserException("Undefined reference `{$key}`.`{$criteria}`");
        }

        foreach ($this->references[$key][$criteria] as &$subset) {
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
     * @param string $container
     * @param string $key
     * @param mixed  $criteria
     * @param array  $data
     *
     * @throws ParserException
     */
    protected function mountArray(string $container, string $key, $criteria, array &$data): void
    {
        if (!array_key_exists($criteria, $this->references[$key])) {
            throw new ParserException("Undefined reference `{$key}`.`{$criteria}`");
        }

        foreach ($this->references[$key][$criteria] as &$subset) {
            if (!in_array($data, $subset[$container], true)) {
                $subset[$container][] = &$data;
            }

            unset($subset);
            continue;
        }
    }

    /**
     * Register data result.
     *
     * @param array $data
     */
    abstract protected function push(array &$data);

    /**
     * Fetch record columns from query row, must use data offset to slice required part of query.
     *
     * @param int   $dataOffset
     * @param array $line
     * @return array
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
}
