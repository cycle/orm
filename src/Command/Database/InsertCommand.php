<?php
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

namespace Spiral\Treap\Command\Database;

use Spiral\Database\DatabaseInterface;
use Spiral\Treap\Command\Database\Traits\ContextTrait;
use Spiral\Treap\Command\MutableCommandInterface;

/**
 * Insert data into associated table and provide lastInsertID promise.
 */
class InsertCommand extends DatabaseCommand implements MutableCommandInterface
{
    use ContextTrait;

    /** @var array */
    private $data;

    /** @var null|mixed */
    private $insertID = null;

    /**
     * @param DatabaseInterface $db
     * @param string            $table
     * @param array             $update
     */
    public function __construct(DatabaseInterface $db, string $table, array $update)
    {
        parent::__construct($db, $table);
        $this->data = $update;
    }

    /**
     * {@inheritdoc}
     */
    public function isEmpty(): bool
    {
        return empty($this->data) && empty($this->context);
    }

    /**
     * Insert values, context not included.
     *
     * @return array
     */
    public function getData(): array
    {
        return $this->data;
    }

    /**
     * @return mixed|null
     */
    public function getPrimaryKey()
    {
        return $this->insertID;
    }

    /**
     * Insert data into associated table.
     */
    public function execute()
    {
        $this->insertID = $this->db
            ->insert($this->table)
            ->values($this->context + $this->data)
            ->run();

        parent::execute();
    }
}