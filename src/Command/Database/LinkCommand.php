<?php
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

namespace Spiral\ORM\Command\Database;

use Spiral\Database\DatabaseInterface;
use Spiral\ORM\Command\Database\Traits\WhereTrait;
use Spiral\ORM\Command\FloatingCommandInterface;

// wait until link is established
class LinkCommand extends DatabaseCommand implements FloatingCommandInterface
{
    use  WhereTrait;

    /** @var array */
    private $data;

    /**
     * @param DatabaseInterface $db
     * @param string            $table
     * @param array             $data
     * @param array             $where
     */
    public function __construct(DatabaseInterface $db, string $table, array $data = [], array $where = [])
    {
        parent::__construct($db, $table);
        $this->data = $data;
        $this->where = $where;
    }

    /**
     * {@inheritdoc}
     */
    public function isEmpty(): bool
    {
        return empty($this->data) || empty($this->where);
    }

    /**
     * Update values, context not included.
     *
     * @return array
     */
    public function getData(): array
    {
        return $this->data;
    }

    public function setData(array $data)
    {
        $this->data = $data;
    }

    /**
     * Update data in associated table.
     */
    public function execute()
    {
        if (!$this->isEmpty()) {
            $this->db->update($this->table, $this->data, $this->where)->run();
        }

        parent::execute();
    }

    public function isDelayed(): bool
    {
        return $this->isEmpty();
    }
}