<?php

/**
 * Cycle DataMapper ORM
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

declare(strict_types=1);

namespace Cycle\ORM\Command;

use Spiral\Database\DatabaseInterface;

abstract class DatabaseCommand implements CommandInterface
{
    /** @var DatabaseInterface @internal */
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
    public function execute(): void
    {
        $this->executed = true;
    }

    /**
     * {@inheritdoc}
     */
    public function complete(): void
    {
        // nothing to do
    }

    /**
     * {@inheritdoc}
     */
    public function rollBack(): void
    {
        // nothing to do
    }
}
