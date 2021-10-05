<?php

declare(strict_types=1);

namespace Cycle\ORM\Command\Database;

use Cycle\ORM\Command\DatabaseCommand;
use Cycle\ORM\Command\ScopeCarrierInterface;
use Cycle\ORM\Command\Traits\ErrorTrait;
use Cycle\ORM\Command\Traits\ScopeTrait;
use Cycle\ORM\Exception\CommandException;
use Cycle\ORM\Heap\Node;
use Cycle\ORM\Heap\State;
use Cycle\Database\DatabaseInterface;

final class Delete extends DatabaseCommand implements ScopeCarrierInterface
{
    use ErrorTrait;
    use ScopeTrait;

    /** @var callable|null */
    private $mapper;

    public function __construct(
        DatabaseInterface $db,
        string $table,
        private State $state,
        callable $mapper = null
    ) {
        parent::__construct($db, $table);
        $this->mapper = $mapper;
    }

    public function isReady(): bool
    {
        return $this->isScopeReady();
    }

    /**
     * Inserting data into associated table.
     */
    public function execute(): void
    {
        if ($this->scope === []) {
            throw new CommandException('Unable to execute delete command without a scope.');
        }

        $this->affectedRows = $this->db->delete(
            $this->table,
            $this->mapper === null ? $this->scope : ($this->mapper)($this->scope)
        )->run();
        $this->state->setStatus(Node::DELETED);

        parent::execute();
    }

    public function register(
        string $key,
        mixed $value,
        int $stream = self::DATA
    ): void {
        if ($stream !== self::SCOPE) {
            return;
        }
        if (empty($value)) {
            return;
        }

        $this->setScope($key, $value);
    }
}
