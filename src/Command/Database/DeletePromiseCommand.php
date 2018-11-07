<?php
/**
 * Spiral, Core Components
 *
 * @author Wolfy-J
 */

namespace Spiral\ORM\Commands;


use Spiral\Database\DatabaseInterface;
use Spiral\Treap\Command\Database\DatabaseCommand;
use Spiral\Treap\Command\Database\Traits\ContextTrait;
use Spiral\Treap\Command\Database\Traits\PrimaryKeyTrait;
use Spiral\Treap\Command\Database\Traits\WhereTrait;
use Spiral\Treap\Command\CommandPromiseInterface;

/**
 * Promised delete is a command which delete based on where statement directly linked to it's
 * context
 * (mutable delete).
 *
 * This creates ability to create postponed delete command which where statement will be resolved
 * only later in transactions.
 */
class DeletePromiseCommand extends DatabaseCommand implements CommandPromiseInterface
{
    use PrimaryKeyTrait, ContextTrait, WhereTrait;

    /**
     * Where conditions (short where format).
     *
     * @var array
     */
    private $where = [];

    /**
     * @param DatabaseInterface $db
     * @param string            $table
     * @param array             $where
     */
    public function __construct(DatabaseInterface $db, string $table, array $where)
    {
        parent::__construct($db, $table);
        $this->where = $where;
    }

    /**
     * {@inheritdoc}
     */
    public function isEmpty(): bool
    {
        return empty($this->where) && empty($this->context);
    }

    /**
     * Inserting data into associated table.
     */
    public function execute()
    {
        if (!$this->isEmpty()) {
            $this->db->delete($this->table, $this->context + $this->where)->run();
        }

        parent::execute();
    }
}