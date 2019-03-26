<?php declare(strict_types=1);
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

namespace Cycle\ORM\Command\Database;

use Cycle\ORM\Command\DatabaseCommand;
use Cycle\ORM\Command\ScopeCarrierInterface;
use Cycle\ORM\Command\Traits\ErrorTrait;
use Cycle\ORM\Command\Traits\ScopeTrait;
use Cycle\ORM\Exception\CommandException;
use Spiral\Database\DatabaseInterface;

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
        bool $fresh = false,
        int $stream = self::DATA
    ) {
        if ($fresh || !is_null($value)) {
            $this->freeScope($key);
        }

        $this->setScope($key, $value);
    }
}