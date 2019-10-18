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
use Cycle\ORM\Parser\Traits\ReferenceTrait;

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
    use ReferenceTrait;

    // Typecasting types
    public const STRING  = 1;
    public const INTEGER = 2;
    public const FLOAT   = 3;
    public const BOOL    = 4;

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
    protected $outerKey = null;

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
    protected $typecast = null;

    /** @var AbstractNode[] */
    protected $nodes = [];

    /**
     * @param array       $columns  When columns are empty original line will be returned as result.
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
    }

    /**
     * @param TypecastInterface $typecast
     */
    final public function setTypecast(TypecastInterface $typecast): void
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
    final public function parseRow(int $offset, array $row): int
    {
        $data = $this->fetchData($offset, $row);

        if ($this->deduplicate($data)) {
            $this->collectReferences($data);
            $this->ensurePlaceholders($data);
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
    final public function linkNode(string $container, AbstractNode $node): void
    {
        $this->nodes[$container] = $node;
        $node->container = $container;
        $node->parent = $this;

        if (!empty($node->outerKey)) {
            $this->trackReference($node->outerKey);
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
    final public function joinNode(string $container, AbstractNode $node): void
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
    final public function getNode(string $container): AbstractNode
    {
        if (!isset($this->nodes[$container])) {
            throw new ParserException("Undefined node `{$container}`.");
        }

        return $this->nodes[$container];
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
        } catch (\Exception $e) {
            throw new ParserException(
                'Unable to parse incoming row: ' . $e->getMessage(),
                $e->getCode(),
                $e
            );
        }
    }

    /**
     * Create placeholders for each of sub nodes.
     *
     * @param array $data
     */
    private function ensurePlaceholders(array &$data): void
    {
        //Let's force placeholders for every sub loaded
        foreach ($this->nodes as $name => $node) {
            $data[$name] = $node instanceof ArrayNode ? [] : null;
        }
    }
}
