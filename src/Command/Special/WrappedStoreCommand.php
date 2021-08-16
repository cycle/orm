<?php

declare(strict_types=1);

namespace Cycle\ORM\Command\Special;

use Closure;
use Cycle\ORM\Command\Database\Insert;
use Cycle\ORM\Command\Database\Update;
use Cycle\ORM\Command\StoreCommandInterface;
use Cycle\ORM\Heap\State;
use Spiral\Database\DatabaseInterface;

final class WrappedStoreCommand implements StoreCommandInterface
{
    private StoreCommandInterface $command;

    private ?Closure $beforeExecute = null;

    private function __construct(StoreCommandInterface $command)
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
    ): self {
        return new self(new Insert($db, $table, $state, $primaryKeys, $pkColumn, $mapper));
    }

    public static function createUpdate(
        DatabaseInterface $db,
        string $table,
        State $state,
        array $primaryKeys = [],
        callable $mapper = null
    ): self {
        return new self(new Update($db, $table, $state, $primaryKeys, $mapper));
    }

    public static function wrapStoreCommand(StoreCommandInterface $command): self
    {
        return new self($command);
    }

    public function withBeforeExecute(?callable $callable): self
    {
        $clone = clone $this;
        $clone->beforeExecute = $callable instanceof Closure ? $callable : Closure::fromCallable($callable);
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
            Closure::bind($this->beforeExecute, null, self::class)($this);
        }
        $this->command->execute();
    }

    public function getDatabase(): ?DatabaseInterface
    {
        return $this->command->getDatabase();
    }

    public function hasData(): bool
    {
        return $this->command->hasData();
    }

    public function registerAppendix(string $key, mixed $value): void
    {
        $this->command->registerAppendix($key, $value);
    }

    public function registerColumn(string $key, mixed $value): void
    {
        $this->command->registerColumn($key, $value);
    }
}
