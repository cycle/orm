<?php
/**
 * Spiral, Core Components
 *
 * @author Wolfy-J
 */

namespace Spiral\Cycle\Parser;

use Spiral\Cycle\Exception\ParserException;

/**
 * Provides ability to parse columns of target table and map table all together.
 */
class PivotedNode extends AbstractNode implements ArrayInterface
{
    // Stores information about associated context data
    public const PIVOT_DATA = '@pivot';

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
        // pivot columns are always prior to table columns
        parent::__construct($columns, $outerKey);

        $this->pivotColumns = $pivotColumns;
        $this->innerPivotKey = $innerPivotKey;
        $this->outerPivotKey = $outerPivotKey;
    }

    /**
     * {@inheritdoc}
     */
    protected function push(array &$data)
    {
        if (empty($this->parent)) {
            throw new ParserException("Unable to register data tree, parent is missing.");
        }

        if (is_null($data[$this->outerKey])) {
            //No data was loaded
            return;
        }

        $this->parent->mountArray(
            $this->container,
            $this->outerKey,
            $data[self::PIVOT_DATA][$this->innerPivotKey],
            $data
        );
    }

    /**
     * {@inheritdoc}
     *
     * Method fetches pivot data into sub-array with key "@pivot".
     */
    protected function fetchData(int $dataOffset, array $line): array
    {
        return [
                self::PIVOT_DATA => $this->pivotData($dataOffset, $line)
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
        $pivotData = $data[self::PIVOT_DATA];

        // unique row criteria
        return $pivotData[$this->innerPivotKey] . '.' . $pivotData[$this->outerPivotKey];
    }
}