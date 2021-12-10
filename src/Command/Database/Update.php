<?php

declare(strict_types=1);

namespace Cycle\ORM\Command\Database;

use Cycle\ORM\Command\ScopeCarrierInterface;
use Cycle\ORM\Command\StoreCommand;
use Cycle\ORM\Command\Traits\ErrorTrait;
use Cycle\ORM\Command\Traits\ScopeTrait;
use Cycle\ORM\Exception\CommandException;
use Cycle\ORM\Heap\State;
use Cycle\Database\DatabaseInterface;
use Cycle\ORM\MapperInterface;

/**
 * Update data CAN be modified by parent commands using context.
 *
 * This is conditional command, it would not be executed when no fields are given!
 */
final class Update extends StoreCommand implements ScopeCarrierInterface
{
    use ErrorTrait;
    use ScopeTrait;

    public function __construct(
        DatabaseInterface $db,
        string $table,
        State $state,
        private MapperInterface $mapper,
        /** @var string[] */
        array $primaryKeys
    ) {
        parent::__construct($db, $table, $state);
        $this->waitScope(...$primaryKeys);
    }

    /**
     * Avoid opening transaction when no changes are expected.
     */
    public function getDatabase(): ?DatabaseInterface
    {
        if ($this->scope === [] || $this->state->getChanges() === []) {
            return null;
        }

        return parent::getDatabase();
    }

    public function isReady(): bool
    {
        return $this->isScopeReady();
    }

    public function hasData(): bool
    {
        return $this->columns !== [] || $this->state->getChanges() !== [];
    }

    public function getStoreData(): array
    {
        if ($this->appendix !== []) {
            $this->state->setData($this->appendix);
        }

        $data = $this->state->getChanges();

        return array_merge(
            $this->columns,
            $this->mapper->mapColumns($data)
        );
    }

    /**
     * Update data in associated table.
     */
    public function execute(): void
    {
        if ($this->scope === []) {
            throw new CommandException('Unable to execute update command without a scope.');
        }

        if ($this->appendix !== []) {
            $this->state->setData($this->appendix);
        }

        $allChanges = $changes = $this->state->getChanges();
        $data = $changes !== [] ? $this->mapper->mapColumns($changes) : [];
        $fields = array_keys($changes);
        if ($data !== [] || $this->columns !== []) {
            $this->affectedRows = $this->db
                ->update(
                    $this->table,
                    $this->mapper->uncast(array_merge($this->columns, $data)),
                    $this->mapper->mapColumns($this->scope)
                )
                ->run();
        }

        $this->state->updateTransactionData(
            $fields !== [] && \count($fields) === \count($allChanges) ? null : $fields
        );

        parent::execute();
    }
}
