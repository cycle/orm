<?php

declare(strict_types=1);

namespace Cycle\ORM\Command;

use Spiral\Database\DatabaseInterface;

abstract class DatabaseCommand implements CommandInterface
{
    /** @internal */
    protected ?DatabaseInterface $db = null;

    protected ?string $table = null;

    private bool $executed = false;

    public function __construct(DatabaseInterface $db, string $table = null)
    {
        $this->db = $db;
        $this->table = $table;
    }

    public function getDatabase(): ?DatabaseInterface
    {
        return $this->db;
    }

    public function isExecuted(): bool
    {
        return $this->executed;
    }

    public function execute(): void
    {
        $this->executed = true;
    }

    public function complete(): void
    {
        // nothing to do
    }

    public function rollBack(): void
    {
        // nothing to do
    }
}
