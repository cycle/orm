<?php

declare(strict_types=1);

namespace Cycle\ORM\Select;

use Spiral\Database\DatabaseInterface;

final class Source implements SourceInterface
{
    private DatabaseInterface $database;

    private string $table;

    private ?ConstrainInterface $constrain = null;

    public function __construct(DatabaseInterface $database, string $table)
    {
        $this->database = $database;
        $this->table = $table;
    }

    public function getDatabase(): DatabaseInterface
    {
        return $this->database;
    }

    public function getTable(): string
    {
        return $this->table;
    }

    public function withConstrain(?ConstrainInterface $constrain): SourceInterface
    {
        $source = clone $this;
        $source->constrain = $constrain;

        return $source;
    }

    public function getConstrain(): ?ConstrainInterface
    {
        return $this->constrain;
    }
}
