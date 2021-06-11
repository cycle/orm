<?php

declare(strict_types=1);

namespace Cycle\ORM\Command\Database;

use Cycle\ORM\Command\DatabaseCommand;
use Cycle\ORM\Command\ScopeCarrierInterface;
use Cycle\ORM\Command\Traits\ErrorTrait;
use Cycle\ORM\Command\Traits\ScopeTrait;
use Cycle\ORM\Command\StoreCommandInterface;
use Cycle\ORM\Exception\CommandException;
use Cycle\ORM\Heap\Node;
use Cycle\ORM\Heap\State;
use Spiral\Database\DatabaseInterface;

/**
 * Update data CAN be modified by parent commands using context.
 *
 * This is conditional command, it would not be executed when no fields are given!
 */
final class Update extends DatabaseCommand implements StoreCommandInterface, ScopeCarrierInterface
{
    use ScopeTrait;
    use ErrorTrait;

    protected array $appendix = [];

    private State $state;
    /** @var null|callable */
    private $mapper;
    private Node $node;

    public function __construct(
        DatabaseInterface $db,
        string $table,
        Node $node,
        array $primaryKeys = [],
        callable $mapper = null
    ) {
        parent::__construct($db, $table);
        $this->waitScope(...$primaryKeys);
        $this->node = $node;
        $this->state = $node->getState();
        $this->mapper = $mapper;
    }

    /**
     * Avoid opening transaction when no changes are expected.
     */
    public function getDatabase(): ?DatabaseInterface
    {
        if ($this->scope === [] || !$this->node->hasChanges()) {
            return null;
        }

        return parent::getDatabase();
    }

    public function isReady(): bool
    {
        return $this->isScopeReady();
    }

    public function hasData(): bool
    {
        return count($this->appendix) > 0 || $this->node->hasChanges();
    }

    /**
     * Update data in associated table.
     */
    public function execute(): void
    {
        if ($this->scope === []) {
            throw new CommandException('Unable to execute update command without a scope.');
        }

        $data = $this->node->getChanges();
        if ($data !== [] || $this->appendix !== []) {
            $this->db
                ->update(
                    $this->table,
                    ($this->mapper === null ? $data : ($this->mapper)($data)) + $this->appendix,
                    $this->mapper === null ? $this->scope : ($this->mapper)($this->scope)
                )
                ->run();
        }
        $this->state->setStatus(Node::MANAGED);
        $this->state->updateTransactionData();

        parent::execute();
    }

    public function register(string $key, $value, bool $fresh = false, int $stream = self::DATA): void
    {
        if ($stream === self::SCOPE) {
            if (empty($value)) {
                return;
            }

            $this->freeScope($key);
            $this->setScope($key, $value);

            return;
        }

        if ($fresh || $value !== null) {
            $this->state->freeContext($key);
        }

        if ($fresh) {
            // we only accept context when context has changed to avoid un-necessary
            // update commands
            $this->state->setContext($key, $value);
        }
    }

    /**
     * Register optional value to store in database. Having this value would not cause command to be executed
     * if data or context is empty.
     *
     * Example: $update->registerAppendix("updated_at", new DateTime());
     *
     * @param mixed  $value
     */
    public function registerAppendix(string $key, $value): void
    {
        $this->appendix[$key] = $value;
    }
}
