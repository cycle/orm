<?php
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

namespace Spiral\Cycle\Select;

use Spiral\Cycle\FactoryInterface;
use Spiral\Database\DatabaseInterface;

class Source implements SourceInterface
{
    /** @var string */
    private $database;

    /** @var string */
    private $table;

    /** @var FactoryInterface @internal */
    private $factory;

    /**
     * @param FactoryInterface $orm
     * @param string           $database
     * @param string           $table
     */
    public function __construct(FactoryInterface $orm, string $database, string $table)
    {
        $this->factory = $orm;
        $this->database = $database;
        $this->table = $table;
    }

    /**
     * @inheritdoc
     */
    public function getDatabase(): DatabaseInterface
    {
        return $this->factory->database($this->database);
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
        return null;
    }
}