<?php
declare(strict_types=1);
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

namespace Spiral\Cycle\Schema\Definition;

/**
 * Entity specific declaration of the index.
 */
final class Index
{
    /** @var string|null */
    private $name;

    /** @var array */
    private $columns;

    /** @var bool */
    private $unique;

    /**
     * @param array       $columns
     * @param string|null $name
     * @param bool        $unique
     */
    public function __construct(array $columns, string $name = null, bool $unique = false)
    {
        $this->columns = $columns;
        $this->name = $name;
        $this->unique = $unique;
    }

    /**
     * @return array
     */
    public function getColumns(): array
    {
        return $this->columns;
    }

    /**
     * @return bool
     */
    public function isUnique(): bool
    {
        return $this->unique;
    }

    /**
     * Generate unique index name.
     *
     * @return string
     */
    public function getName(): string
    {
        if (!is_null($this->name)) {
            return $this->name;
        }

        $name = ($this->isUnique() ? 'unique_' : 'index_') . join('_', $this->getColumns());

        return strlen($name) > 64 ? md5($name) : $name;
    }

    /**
     * So we can compare indexes.
     *
     * @return string
     */
    public function __toString(): string
    {
        return json_encode([$this->columns, $this->unique]);
    }
}