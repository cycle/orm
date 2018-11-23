<?php
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

namespace Spiral\ORM\Command;

use Spiral\Database\DatabaseInterface;

abstract class DatabaseCommand extends AbstractCommand
{
    /**
     * @invisible
     * @var DatabaseInterface
     */
    protected $db;

    /** @var string|null */
    protected $table;

    /**
     * @param DatabaseInterface $db
     * @param string            $table
     */
    public function __construct(DatabaseInterface $db, string $table = null)
    {
        $this->db = $db;
        $this->table = $table;
    }

    /**
     * @return DatabaseInterface
     */
    public function getDatabase(): ?DatabaseInterface
    {
        return $this->db;
    }
}