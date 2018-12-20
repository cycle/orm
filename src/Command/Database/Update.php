<?php
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */
declare(strict_types=1);

namespace Spiral\Cycle\Command\Database;

use Spiral\Cycle\Command\ContextCarrierInterface;
use Spiral\Cycle\Command\DatabaseCommand;
use Spiral\Cycle\Command\ScopeCarrierInterface;
use Spiral\Cycle\Command\Traits\ContextTrait;
use Spiral\Cycle\Command\Traits\ErrorTrait;
use Spiral\Cycle\Command\Traits\ScopeTrait;
use Spiral\Cycle\Exception\CommandException;
use Spiral\Database\DatabaseInterface;

/**
 * Update data CAN be modified by parent commands using context.
 *
 * This is conditional command, it would not be executed when no fields are given!
 */
class Update extends DatabaseCommand implements ContextCarrierInterface, ScopeCarrierInterface
{
    use ContextTrait, ScopeTrait, ErrorTrait;

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

    /**
     * {@inheritdoc}
     */
    protected function isEmpty(): bool
    {
        return (empty($this->data) && empty($this->context)) || empty($this->scope);
    }


    /**
     * @inheritdoc
     */
    public function register(string $key, $value, bool $fresh = false, int $stream = self::DATA)
    {
        if ($stream == self::SCOPE) {
            if (empty($value)) {
                return;
            }

            $this->freeScope($key);
            $this->setScope($key, $value);

            return;
        }

        if ($fresh || !is_null($value)) {
            $this->freeContext($key);
        }

        if ($fresh) {
            // we only accept context when context has changed to avoid un-necessary
            // update commands
            $this->setContext($key, $value);
        }
    }
}