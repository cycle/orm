<?php

declare(strict_types=1);

namespace Cycle\ORM\Command\Database;

use Cycle\Database\DatabaseInterface;
use Cycle\Database\Query\ReturningInterface;
use Cycle\ORM\Command\StoreCommand;
use Cycle\ORM\Command\Traits\ErrorTrait;
use Cycle\ORM\Command\Traits\MapperTrait;
use Cycle\ORM\Heap\State;
use Cycle\ORM\MapperInterface;
use Cycle\ORM\SchemaInterface;

/**
 * Insert data into associated table and provide lastInsertID promise.
 */
final class Insert extends StoreCommand
{
    use ErrorTrait;
    use MapperTrait;

    /**
     * @param non-empty-string $table
     * @param string[] $primaryKeys
     * @param non-empty-string|null $pkColumn
     * @param array<non-empty-string, int> $generated
     */
    public function __construct(
        DatabaseInterface $db,
        string $table,
        State $state,
        ?MapperInterface $mapper,
        private array $primaryKeys = [],
        private ?string $pkColumn = null,
        private array $generated = [],
    ) {
        parent::__construct($db, $table, $state);
        $this->mapper = $mapper;
    }

    public function isReady(): bool
    {
        return true;
    }

    public function hasData(): bool
    {
        return match (true) {
            $this->columns !== [],
            $this->state->getData() !== [],
            $this->hasGeneratedFields() => true,
            default => false,
        };
    }

    public function getStoreData(): array
    {
        if ($this->appendix !== []) {
            $this->state->setData($this->appendix);
            $this->appendix = [];
        }
        $data = $this->state->getData();
        return array_merge($this->columns, $this->mapper?->mapColumns($data) ?? $data);
    }

    /**
     * Insert data into associated table.
     */
    public function execute(): void
    {
        $state = $this->state;
        $returningFields = [];

        if ($this->appendix !== []) {
            $state->setData($this->appendix);
        }

        $uncasted = $data = $state->getData();

        // filter PK null values
        foreach ($this->primaryKeys as $key) {
            if (!isset($uncasted[$key])) {
                unset($uncasted[$key]);
            }
        }
        // unset db-generated fields if they are null
        foreach ($this->generated as $column => $mode) {
            if (($mode & SchemaInterface::GENERATED_DB) === 0x0) {
                continue;
            }

            $returningFields[$column] = $mode;
            if (!isset($uncasted[$column])) {
                unset($uncasted[$column]);
            }
        }
        $uncasted = $this->prepareData($uncasted);

        $insert = $this->db
            ->insert($this->table)
            ->values(\array_merge($this->columns, $uncasted));

        if ($this->pkColumn !== null && $returningFields === []) {
            $returningFields[$this->primaryKeys[0]] ??= $this->pkColumn;
        }

        if ($insert instanceof ReturningInterface && $returningFields !== []) {
            // Map generated fields to columns
            $returning = $this->mapper->mapColumns($returningFields);
            // Array of [field name => column name]
            $returning = \array_combine(\array_keys($returningFields), \array_keys($returning));

            $insert->returning(...\array_values($returning));

            $insertID = $insert->run();

            if (\count($returning) === 1) {
                $field = \array_key_first($returning);
                $state->register(
                    $field,
                    $this->mapper === null ? $insertID : $this->mapper->cast([$field => $insertID])[$field],
                );
            } else {
                foreach ($this->mapper->cast($insertID) as $field => $value) {
                    $state->register($field, $value);
                }
            }
        } else {
            $insertID = $insert->run();

            if ($insertID !== null && \count($this->primaryKeys) === 1) {
                $fpk = $this->primaryKeys[0]; // first PK
                if (!isset($data[$fpk])) {
                    $state->register(
                        $fpk,
                        $this->mapper === null ? $insertID : $this->mapper->cast([$fpk => $insertID])[$fpk]
                    );
                }
            }
        }


        $state->updateTransactionData();

        parent::execute();
    }

    public function register(string $key, mixed $value): void
    {
        $this->state->register($key, $value);
    }

    /**
     * Has fields that weren't provided but will be generated by the database or PHP.
     */
    private function hasGeneratedFields(): bool
    {
        if ($this->generated === []) {
            return false;
        }

        $data = $this->state->getData();

        foreach ($this->generated as $field => $mode) {
            if (($mode & (SchemaInterface::GENERATED_DB | SchemaInterface::GENERATED_PHP_INSERT)) === 0x0) {
                continue;
            }

            if (!isset($data[$field])) {
                return true;
            }
        }

        return true;
    }
}
