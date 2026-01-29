<?php

declare(strict_types=1);

namespace Cycle\ORM\Command\Special;

use Cycle\ORM\Command\CommandInterface;
use Cycle\ORM\Command\Database\Insert;
use Cycle\ORM\Command\Database\Update;
use Cycle\ORM\Heap\State;
use Cycle\Database\DatabaseInterface;
use Cycle\ORM\MapperInterface;

class WrappedCommand implements CommandInterface
{
    private ?\Closure $beforeExecute = null;
    private ?\Closure $afterExecute = null;

    protected function __construct(
        protected CommandInterface $command,
    ) {}

    public static function createInsert(
        DatabaseInterface $db,
        string $table,
        State $state,
        ?MapperInterface $mapper,
        array $primaryKeys = [],
        ?string $pkColumn = null,
    ): WrappedStoreCommand {
        return new WrappedStoreCommand(new Insert($db, $table, $state, $mapper, $primaryKeys, $pkColumn));
    }

    public static function createUpdate(
        DatabaseInterface $db,
        string $table,
        State $state,
        ?MapperInterface $mapper,
        array $primaryKeys = [],
    ): WrappedStoreCommand {
        return new WrappedStoreCommand(new Update($db, $table, $state, $mapper, $primaryKeys));
    }

    public static function wrapCommand(CommandInterface $command): static
    {
        return new static($command);
    }

    public function withBeforeExecution(?callable $callable): static
    {
        $clone = clone $this;
        $clone->beforeExecute = $callable instanceof \Closure ? $callable : \Closure::fromCallable($callable);
        return $clone;
    }

    public function withAfterExecution(?callable $callable): static
    {
        $clone = clone $this;
        $clone->afterExecute = $callable instanceof \Closure ? $callable : \Closure::fromCallable($callable);
        return $clone;
    }

    public function isReady(): bool
    {
        return $this->command->isReady();
    }

    public function isExecuted(): bool
    {
        return $this->command->isExecuted();
    }

    public function execute(): void
    {
        if ($this->beforeExecute !== null) {
            \Closure::bind($this->beforeExecute, null, static::class)($this->command);
        }
        $this->command->execute();
        if ($this->afterExecute !== null) {
            \Closure::bind($this->afterExecute, null, static::class)($this->command);
        }
    }

    public function getDatabase(): ?DatabaseInterface
    {
        return $this->command->getDatabase();
    }

    public function getCommand(): CommandInterface
    {
        return $this->command;
    }
}
