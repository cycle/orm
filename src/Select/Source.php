<?php
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */
declare(strict_types=1);

namespace Spiral\Cycle\Select;

use Spiral\Database\DatabaseInterface;

final class Source implements SourceInterface
{
    /** @var DatabaseInterface */
    private $database;

    /** @var string */
    private $table;

    /** @var ConstrainInterface[] */
    private $constrains = [];

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
    public function withConstrain(string $name, ?ConstrainInterface $constrain): SourceInterface
    {
        $source = clone $this;
        $source->constrains[$name] = $constrain;

        return $source;
    }

    /**
     * @inheritdoc
     */
    public function getConstrain(string $name = self::DEFAULT_CONSTRAIN): ?ConstrainInterface
    {
        return $this->constrains[$name] ?? null;
    }
}