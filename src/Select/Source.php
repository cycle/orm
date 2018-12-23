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
    public function getConstrain(string $name = self::DEFAULT_CONSTRAIN): ?ConstrainInterface
    {
        // todo: pass constrains
        return null;
    }
}