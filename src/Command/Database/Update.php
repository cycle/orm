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
use Spiral\ORM\Command\ScopedInterface;
use Spiral\ORM\Command\Traits\ContextTrait;
use Spiral\ORM\Command\Traits\ScopeTrait;
use Spiral\ORM\Exception\CommandException;

/**
 * Update data CAN be modified by parent commands using context.
 *
 * This is conditional command, it would not be executed when no fields are given!
 */
class Update extends DatabaseCommand implements ContextualInterface, ScopedInterface
{
    use ContextTrait, ScopeTrait;

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
        $this->scope = $where;
    }

    /**
     * Avoid opening transaction when no changes are expected.
     *
     * @return null|DatabaseInterface
     */
    public function getDatabase(): ?DatabaseInterface
    {
        if ($this->isEmpty()) {
            return null;
        }

        return parent::getDatabase();
    }

    /**
     * @inheritdoc
     */
    public function isReady(): bool
    {
        return empty($this->waitContext) && empty($this->waitScope);
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
        if (empty($this->scope)) {
            throw new CommandException("Unable to execute update command without a scope");
        }

        if (!$this->isEmpty()) {
            $this->db->update($this->table, $this->getData(), $this->scope)->run();
        }

        parent::execute();
    }

    public function accept($column, $value)
    {
        if (strpos($column, 'scope:') === 0) {
            $column = substr($column, 6);
            unset($this->waitScope[$column]);
            $this->scope[$column] = $value;
        } else {
            unset($this->waitContext[$column]);
            $this->waitContext[$column] = $value;
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function isEmpty(): bool
    {
        return (empty($this->data) && empty($this->context)) || empty($this->scope);
    }
}