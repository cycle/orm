<?php
/**
 * Spiral, Core Components
 *
 * @author Wolfy-J
 */
declare(strict_types=1);

namespace Spiral\Cycle\Parser;


use Spiral\Cycle\Exception\ParserException;

/**
 * Similar to normal pivot node but does not require parent!
 */
class PivotedRootNode extends OutputNode
{
    /** @var array */
    private $pivotColumns = [];

    /** @var string */
    private $innerPivotKey;

    /** @var string */
    private $outerPivotKey;

    /**
     * @param array  $columns
     * @param array  $pivotColumns
     * @param string $outerKey
     * @param string $innerPivotKey
     * @param string $outerPivotKey
     */
    public function __construct(
        array $columns,
        array $pivotColumns,
        string $outerKey,
        string $innerPivotKey,
        string $outerPivotKey
    ) {
        //Pivot columns are always prior to table columns
        parent::__construct($columns, $outerKey);

        $this->pivotColumns = $pivotColumns;
        $this->innerPivotKey = $innerPivotKey;
        $this->outerPivotKey = $outerPivotKey;
    }

    /**
     * {@inheritdoc}
     *
     * Method fetches pivot data into sub-array with key "@pivot".
     */
    protected function fetchData(int $dataOffset, array $line): array
    {
        return [
                PivotedNode::PIVOT_DATA => $this->pivotData($dataOffset, $line)
            ] + parent::fetchData($dataOffset + count($this->pivotColumns), $line);
    }

    /**
     * Fetch record columns from query row, must use data offset to slice required part of query.
     *
     * @param int   $dataOffset
     * @param array $line
     * @return array
     */
    protected function pivotData(int $dataOffset, array $line): array
    {
        try {
            //Combine column names with sliced piece of row
            return array_combine(
                $this->pivotColumns,
                array_slice($line, $dataOffset, count($this->pivotColumns))
            );
        } catch (\Exception $e) {
            throw new ParserException(
                "Unable to parse incoming row: " . $e->getMessage(),
                $e->getCode(),
                $e
            );
        }
    }

    /**
     * De-duplication in pivot tables based on values in pivot table.
     *
     * @param array $data
     * @return string
     */
    protected function duplicateCriteria(array &$data): string
    {
        $pivotData = $data[PivotedNode::PIVOT_DATA];

        return $pivotData[$this->innerPivotKey] . '.' . $pivotData[$this->outerPivotKey];
    }
}