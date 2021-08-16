<?php

declare(strict_types=1);

namespace Cycle\ORM\Command\Special;

use Closure;
use Cycle\ORM\Command\CommandInterface;
use Cycle\ORM\Command\Database\Insert;
use Cycle\ORM\Command\Database\Update;
use Cycle\ORM\Heap\State;
use Spiral\Database\DatabaseInterface;

class WrappedCommand implements CommandInterface
{
    protected CommandInterface $command;

    private ?Closure $beforeExecute = null;

    private ?Closure $afterExecute = null;

    protected function __construct(CommandInterface $command)
    {
        $this->command = $command;
    }

    public static function createInsert(
        DatabaseInterface $db,
        string $table,
        State $state,
        array $primaryKeys = [],
        string $pkColumn = null,
        callable $mapper = null
    ): static {
        return new static(new Insert($db, $table, $state, $primaryKeys, $pkColumn, $mapper));
    }

    public static function createUpdate(
        DatabaseInterface $db,
        string $table,
        State $state,
        array $primaryKeys = [],
        callable $mapper = null
    ): static {
        return new static(new Update($db, $table, $state, $primaryKeys, $mapper));
    }

    public static function wrapCommand(CommandInterface $command): static
    {
        return new static($command);
    }

    public function withBeforeExecution(?callable $callable): static
    {
        $clone = clone $this;
        $clone->beforeExecute = $callable instanceof Closure ? $callable : Closure::fromCallable($callable);
        return $clone;
    }

    public function withAfterExecution(?callable $callable): static
    {
        $clone = clone $this;
        $clone->afterExecute = $callable instanceof Closure ? $callable : Closure::fromCallable($callable);
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
            Closure::bind($this->beforeExecute, null, static::class)($this);
        }
        $this->command->execute();
        if ($this->afterExecute !== null) {
            Closure::bind($this->afterExecute, null, static::class)($this);
        }
    }

    public function getDatabase(): ?DatabaseInterface
    {
        return $this->command->getDatabase();
    }

    public function hasData(): bool
    {
        return $this->command->hasData();
    }
}
