<?php
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

namespace Spiral\ORM\Command;

use Spiral\Database\DatabaseInterface;

abstract class DatabaseCommand implements CommandInterface
{
    /**
     * @invisible
     * @var DatabaseInterface
     */
    protected $db;

    /** @var string|null */
    protected $table;

    /** @var bool */
    private $executed = false;

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

    /**
     * {@inheritdoc}
     */
    public function isExecuted(): bool
    {
        return $this->executed;
    }

    /**
     * {@inheritdoc}
     */
    public function execute()
    {
        $this->executed = true;
    }

    /**
     * {@inheritdoc}
     */
    public function complete()
    {
        // nothing to do
    }

    /**
     * {@inheritdoc}
     */
    public function rollBack()
    {
        // nothing to do
    }
}