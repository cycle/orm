<?php

/**
 * Cycle DataMapper ORM
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

declare(strict_types=1);

namespace Cycle\ORM\Select;

use Spiral\Database\DatabaseInterface;

final class Source implements SourceInterface
{
    /** @var DatabaseInterface */
    private $database;

    /** @var string */
    private $table;

    /** @var ConstrainInterface|null */
    private $constrain = null;

    /**
     * @param DatabaseInterface $database
     * @param string            $table
     */
    public function __construct(DatabaseInterface $database, string $table)
    {
        $this->database = $database;
        $this->table = $table;
    }

    /**
     * @inheritdoc
     */
    public function getDatabase(): DatabaseInterface
    {
        return $this->database;
    }

    /**
     * @inheritdoc
     */
    public function getTable(): string
    {
        return $this->table;
    }

    /**
     * @inheritdoc
     */
    public function withConstrain(?ConstrainInterface $constrain): SourceInterface
    {
        $source = clone $this;
        $source->constrain = $constrain;

        return $source;
    }

    /**
     * @inheritdoc
     */
    public function getConstrain(): ?ConstrainInterface
    {
        return $this->constrain;
    }
}
