<?php
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

namespace Spiral\ORM\Command\Database;

use Spiral\Database\DatabaseInterface;
use Spiral\ORM\Command\ContextualInterface;
use Spiral\ORM\Command\DatabaseCommand;
use Spiral\ORM\Command\Traits\ContextTrait;

/**
 * Insert data into associated table and provide lastInsertID promise.
 */
class Insert extends DatabaseCommand implements ContextualInterface
{
    use ContextTrait;

    /** @var array */
    private $data;

    /** @var null|mixed */
    private $insertID = null;

    /**
     * @param DatabaseInterface $db
     * @param string            $table
     * @param array             $data
     */
    public function __construct(DatabaseInterface $db, string $table, array $data = [])
    {
        parent::__construct($db, $table);
        $this->data = $data;
    }

    /**
     * @inheritdoc
     */
    public function isReady(): bool
    {
        return empty($this->waitContext);
    }

    /**
     * Insert values, context not included.
     *
     * @return array
     */
    public function getData(): array
    {
        return array_merge($this->data, $this->context);
    }

    /**
     * @return mixed|null
     */
    public function getInsertID()
    {
        return $this->insertID;
    }

    private $target;
    private $targetColumn;

    public function onInsert($target, $column)
    {
        $this->target = $target;
        $this->targetColumn = $column;
    }


    public function accept($column, $value)
    {
        unset($this->waitContext[$column]);
        $this->waitContext[$column] = $value;
    }

    /**
     * Insert data into associated table.
     */
    public function execute()
    {
        $this->insertID = $this->db->insert($this->table)->values($this->getData())->run();

        if (!empty($this->target)) {
            call_user_func([$this->target, 'accept'], $this->targetColumn, $this->insertID);
        }

        parent::execute();
    }
}