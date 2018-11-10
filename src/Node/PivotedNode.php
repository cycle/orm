<?php
/**
 * Spiral, Core Components
 *
 * @author Wolfy-J
 */

namespace Spiral\ORM\Node;

use Spiral\ORM\Exception\NodeException;

/**
 * Provides ability to parse columns of target table and map table all together.
 */
class PivotedNode extends AbstractNode implements ArrayInterface
{
    // Stores information about associated context data
    public const PIVOT_DATA = '@pivot';

    /** @var int */
    private $countPivot = 0;

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
        parent::__construct(array_merge($pivotColumns, $columns), $outerKey);
        $this->countPivot = count($pivotColumns);

        $this->innerPivotKey = $innerPivotKey;
        $this->outerPivotKey = $outerPivotKey;
    }

    /**
     * {@inheritdoc}
     */
    protected function push(array &$data)
    {
        if (empty($this->parent)) {
            throw new NodeException("Unable to register data tree, parent is missing.");
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
        $data = parent::fetchData($dataOffset, $line);

        // forming pivot data presence
        return array_merge(
            [self::PIVOT_DATA => array_slice($data, 0, $this->countPivot)],
            array_slice($data, $this->countPivot)
        );
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