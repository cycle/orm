<?php

declare(strict_types=1);

namespace Cycle\ORM\Command;

use Cycle\ORM\Heap\State;
use Cycle\Database\DatabaseInterface;

abstract class StoreCommand extends DatabaseCommand implements StoreCommandInterface
{
    protected array $columns = [];
    protected array $appendix = [];

    public function __construct(
        DatabaseInterface $db,
        ?string $table,
        protected State $state,
    ) {
        parent::__construct($db, $table);
    }

    /**
     * @return array<string, mixed> Where Keys are DB filed names
     */
    abstract public function getStoreData(): array;

    public function registerColumn(string $key, mixed $value): void
    {
        $this->columns[$key] = $value;
    }

    public function registerAppendix(string $key, mixed $value): void
    {
        $this->appendix[$key] = $value;
    }

    /**
     * @internal
     */
    public function setDatabase(?DatabaseInterface $db): void
    {
        $this->db = $db;
    }
}
