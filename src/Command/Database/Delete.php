<?php
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

namespace Spiral\Cycle\Command\Database;

use Spiral\Database\DatabaseInterface;
use Spiral\Cycle\Command\DatabaseCommand;
use Spiral\Cycle\Command\ScopeCarrierInterface;
use Spiral\Cycle\Command\Traits\ErrorTrait;
use Spiral\Cycle\Command\Traits\ScopeTrait;
use Spiral\Cycle\Exception\CommandException;

class Delete extends DatabaseCommand implements ScopeCarrierInterface
{
    use ScopeTrait, ErrorTrait;

    /**
     * @param DatabaseInterface $db
     * @param string            $table
     * @param array             $where
     */
    public function __construct(DatabaseInterface $db, string $table, array $where = [])
    {
        parent::__construct($db, $table);
        $this->scope = $where;
    }

    /**
     * @inheritdoc
     */
    public function isReady(): bool
    {
        return empty($this->waitScope);
    }

    /**
     * Inserting data into associated table.
     */
    public function execute()
    {
        if (empty($this->scope)) {
            throw new CommandException("Unable to execute delete command without a scope");
        }

        $this->db->delete($this->table, $this->scope)->run();
        parent::execute();
    }

    /**
     * @inheritdoc
     */
    public function register(
        string $key,
        $value,
        bool $update = false,
        int $stream = self::DATA
    ) {
        if ($update || !is_null($value)) {
            $this->freeScope($key);
        }

        $this->setScope($key, $value);
    }
}