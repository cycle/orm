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

class DeleteCommand extends DatabaseCommand
{
    use WhereTrait;

    /**
     * @param DatabaseInterface $db
     * @param string            $table
     * @param array             $where
     */
    public function __construct(DatabaseInterface $db, string $table, array $where)
    {
        parent::__construct($db, $table);
        $this->where = $where;
    }

    /**
     * Inserting data into associated table.
     */
    public function execute()
    {
        $this->db->delete($this->table, $this->where)->run();
        parent::execute();
    }
}