<?php
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

namespace Spiral\Cycle\Mapper;

use Spiral\Cycle\ORMInterface;
use Spiral\Cycle\Selector\ScopeInterface;
use Spiral\Cycle\Selector\SourceInterface;
use Spiral\Database\DatabaseInterface;

class Source implements SourceInterface
{
    /** @var ORMInterface @internal */
    protected $orm;

    /** @var string */
    protected $database;

    /** @var string */
    protected $table;

    /**
     * @param ORMInterface $orm
     * @param string       $database
     * @param string       $table
     */
    public function __construct(ORMInterface $orm, string $database, string $table)
    {
        $this->orm = $orm;
        $this->database = $database;
        $this->table = $table;
    }

    /**
     * @inheritdoc
     */
    public function getDatabase(): DatabaseInterface
    {
        return $this->orm->getDBAL()->database($this->database);
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
    public function getScope(string $name = self::DEFAULT_SCOPE): ?ScopeInterface
    {
        // todo: implement
        return null;
    }
}