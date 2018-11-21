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
use Spiral\ORM\Command\Database\Traits\WhereTrait;
use Spiral\ORM\Command\ScopedInterface;
use Spiral\ORM\Exception\CommandException;

/**
 * Update data CAN be modified by parent commands using context.
 *
 * This is conditional command, it would not be executed when no fields are given!
 */
class UpdateCommand extends DatabaseCommand implements ContextualInterface, ScopedInterface
{
    use ContextTrait, WhereTrait;

    /** @var array */
    private $data;

    /**
     * @param DatabaseInterface $db
     * @param string            $table
     * @param array             $data
     * @param array             $where
     */
    public function __construct(DatabaseInterface $db, string $table, array $data = [], array $where = [])
    {
        parent::__construct($db, $table);
        $this->data = $data;
        $this->where = $where;
    }

    /**
     * {@inheritdoc}
     */
    public function isEmpty(): bool
    {
        return (empty($this->data) && empty($this->context)) || empty($this->where);
    }

    /**
     * Update values, context not included.
     *
     * @return array
     */
    public function getData(): array
    {
        return array_merge($this->data, $this->context);
    }

    /**
     * Update data in associated table.
     */
    public function execute()
    {
        if (empty($this->where)) {
            throw new CommandException("Unable to execute update command without a scope");
        }

        if (!$this->isEmpty()) {
            $this->db->update($this->table, $this->getData(), $this->where)->run();
        }

        parent::execute();
    }
}