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
use Spiral\ORM\Command\Database\Traits\ContextTrait;

/**
 * Insert data into associated table and provide lastInsertID promise.
 */
class InsertCommand extends DatabaseCommand implements ContextualInterface
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
    public function __construct(DatabaseInterface $db, string $table, array $data)
    {
        parent::__construct($db, $table);
        $this->data = $data;
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
        return array_merge($this->data, $this->context);
    }

    /**
     * @return mixed|null
     */
    public function getInsertID()
    {
        return $this->insertID;
    }

    /**
     * Insert data into associated table.
     */
    public function execute()
    {
        $this->insertID = $this->db->insert($this->table)->values($this->getData())->run();
        parent::execute();
    }
}