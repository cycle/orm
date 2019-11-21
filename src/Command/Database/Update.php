<?php

/**
 * Cycle DataMapper ORM
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

declare(strict_types=1);

namespace Cycle\ORM\Command\Database;

use Cycle\ORM\Command\ContextCarrierInterface;
use Cycle\ORM\Command\DatabaseCommand;
use Cycle\ORM\Command\ScopeCarrierInterface;
use Cycle\ORM\Command\Traits\ContextTrait;
use Cycle\ORM\Command\Traits\ErrorTrait;
use Cycle\ORM\Command\Traits\ScopeTrait;
use Cycle\ORM\Exception\CommandException;
use Spiral\Database\DatabaseInterface;

/**
 * Update data CAN be modified by parent commands using context.
 *
 * This is conditional command, it would not be executed when no fields are given!
 */
final class Update extends DatabaseCommand implements ContextCarrierInterface, ScopeCarrierInterface
{
    use ContextTrait;
    use ScopeTrait;
    use ErrorTrait;

    /** @var array */
    protected $data = [];

    /** @var array */
    protected $appendix = [];

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
        return $this->waitContext === [] && $this->waitScope === [];
    }

    /**
     * Update values, context not included.
     *
     * @return array
     */
    public function getData(): array
    {
        return array_merge($this->data, $this->context, $this->appendix);
    }

    /**
     * Update data in associated table.
     */
    public function execute(): void
    {
        if ($this->scope === []) {
            throw new CommandException('Unable to execute update command without a scope');
        }

        if (!$this->isEmpty()) {
            $this->db->update($this->table, $this->getData(), $this->scope)->run();
        }

        parent::execute();
    }

    /**
     * {@inheritdoc}
     */
    public function isEmpty(): bool
    {
        return ($this->data === [] && $this->context === []) || $this->scope === [];
    }

    /**
     * @inheritdoc
     */
    public function register(string $key, $value, bool $fresh = false, int $stream = self::DATA): void
    {
        if ($stream == self::SCOPE) {
            if (empty($value)) {
                return;
            }

            $this->freeScope($key);
            $this->setScope($key, $value);

            return;
        }

        if ($fresh || $value !== null) {
            $this->freeContext($key);
        }

        if ($fresh) {
            // we only accept context when context has changed to avoid un-necessary
            // update commands
            $this->setContext($key, $value);
        }
    }

    /**
     * Register optional value to store in database. Having this value would not cause command to be executed
     * if data or context is empty.
     *
     * Example: $update->registerAppendix("updated_at", new DateTime());
     *
     * @param string $key
     * @param mixed  $value
     */
    public function registerAppendix(string $key, $value): void
    {
        $this->appendix[$key] = $value;
    }
}
