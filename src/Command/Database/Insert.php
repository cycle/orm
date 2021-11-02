<?php

declare(strict_types=1);

namespace Cycle\ORM\Command\Database;

use Cycle\Database\DatabaseInterface;
use Cycle\Database\Driver\Postgres\Query\PostgresInsertQuery;
use Cycle\ORM\Command\StoreCommand;
use Cycle\ORM\Command\Traits\ErrorTrait;
use Cycle\ORM\Heap\State;

/**
 * Insert data into associated table and provide lastInsertID promise.
 */
final class Insert extends StoreCommand
{
    use ErrorTrait;

    /** @var callable|null */
    private $mapper;

    /** @var callable|null */
    private $caster;

    /**
     * @param callable|null $mapper Optional callable that calls {@see MapperInterface::mapColumns()} method.
     * @param callable|null $caster Optional callable that calls {@see TypecastInterface::cast()} method.
     */
    public function __construct(
        DatabaseInterface $db,
        string $table,
        State $state,
        /** @var string[] */
        private array $primaryKeys = [],
        private ?string $pkColumn = null,
        callable $mapper = null,
        callable $caster = null
    ) {
        parent::__construct($db, $table, $state);
        $this->mapper = $mapper;
        $this->caster = $caster;
    }

    public function isReady(): bool
    {
        return true;
    }

    public function hasData(): bool
    {
        return $this->columns !== [] || $this->state->getData() !== [];
    }

    public function getStoreData(): array
    {
        if ($this->appendix !== []) {
            $this->state->setData($this->appendix);
            $this->appendix = [];
        }
        $data = $this->state->getData();
        return array_merge($this->columns, $this->mapper === null ? $data : ($this->mapper)($data));
    }

    /**
     * Insert data into associated table.
     */
    public function execute(): void
    {
        $state = $this->state;

        if ($this->appendix !== []) {
            $state->setData($this->appendix);
        }

        $data = $state->getData();

        // filter PK null values
        foreach ($this->primaryKeys as $key) {
            if (!isset($data[$key])) {
                unset($data[$key]);
            }
        }

        $insert = $this->db
            ->insert($this->table)
            ->values(array_merge(
                $this->columns,
                $this->mapper === null ? $data : ($this->mapper)($data)
            ));
        if ($this->pkColumn !== null && $insert instanceof PostgresInsertQuery) {
            $insert->returning($this->pkColumn);
        }
        $insertID = $insert->run();

        if ($insertID !== null && \count($this->primaryKeys) === 1) {
            $fpk = $this->primaryKeys[0]; // first PK
            if (!isset($data[$fpk])) {
                $state->register(
                    $fpk,
                    $this->caster === null
                    ? $insertID
                    : ($this->caster)([$fpk => $insertID])[$fpk]
                );
            }
        }
        $state->updateTransactionData();

        parent::execute();
    }
}
