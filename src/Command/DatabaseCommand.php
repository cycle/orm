<?php

declare(strict_types=1);

namespace Cycle\ORM\Command;

use Cycle\Database\DatabaseInterface;

abstract class DatabaseCommand implements CommandInterface
{
    private bool $executed = false;

    public function __construct(
        /** @internal */
        protected DatabaseInterface $db,
        protected ?string $table = null,
    ) {}

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
}
