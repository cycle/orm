<?php

declare(strict_types=1);

namespace Cycle\ORM\Command\Database;

use Cycle\ORM\Command\DatabaseCommand;
use Cycle\ORM\Command\InitCarrierInterface;
use Cycle\ORM\Command\Traits\ContextTrait;
use Cycle\ORM\Command\Traits\ErrorTrait;
use Cycle\ORM\Context\ConsumerInterface;
use Cycle\ORM\Context\ProducerInterface;
use Cycle\ORM\Exception\CommandException;
use Cycle\ORM\Heap\Node;
use Cycle\ORM\Heap\State;
use Spiral\Database\DatabaseInterface;
use Spiral\Database\Driver\Postgres\Query\PostgresInsertQuery;

/**
 * Insert data into associated table and provide lastInsertID promise.
 */
final class Insert extends DatabaseCommand implements InitCarrierInterface, ProducerInterface
{
    use ContextTrait;
    use ErrorTrait;

    /**
     * Special identifier to forward insert key into
     */
    public const INSERT_ID = '@lastInsertID';

    protected array $data;

    /** @var string[] */
    protected array $primaryKeys;

    private State $state;

    /** @var null|callable */
    private $mapper;

    public function __construct(
        DatabaseInterface $db,
        string $table,
        State $state,
        array $primaryKeys = [],
        callable $mapper = null
    ) {
        parent::__construct($db, $table);
        $this->primaryKeys = $primaryKeys;
        $this->state = $state;
        $this->mapper = $mapper;
    }

    public function isReady(): bool
    {
        return $this->isContextReady();
    }

    /**
     * Triggers only after command execution!
     */
    public function forward(
        string $key,
        ConsumerInterface $consumer,
        string $target,
        bool $trigger = false,
        int $stream = ConsumerInterface::DATA
    ): void {
        if ($trigger) {
            throw new CommandException('Insert command can only forward keys after the execution.');
        }

        $this->state->forward($key, $consumer, $target, $trigger, $stream);
    }

    public function register(string $key, $value, bool $fresh = false, int $stream = self::DATA): void
    {
        if ($fresh || $value !== null) {
            $this->state->freeContext($key);
        }

        $this->state->setContext($key, $value);
    }

    /**
     * Insert data into associated table.
     */
    public function execute(): void
    {
        $data = $this->state->getData();

        $insert = $this->db
            ->insert($this->table)
            ->values($this->mapper === null ? $data : ($this->mapper)($data));
        if (count($this->primaryKeys) === 1 && $insert instanceof PostgresInsertQuery) {
            $insert->returning($this->primaryKeys[0]);
        }

        $insertID = $insert->run();

        // foreach ($this->consumers as $key => $consumers) {
        //     $fresh = true;
        //     if ($key === self::INSERT_ID) {
        //         $value = $insertID;
        //     } else {
        //         $value = $data[$key] ?? null;
        //     }
        //
        // foreach ($this->consumers as $key => $consumers) {
        //     $fresh = true;
        //     if ($key === self::INSERT_ID) {
        //         $value = $insertID;
        //     } else {
        //         $value = $data[$key] ?? null;
        //     }
        //
        //     foreach ($consumers as $id => $consumer) {
        //         /** @var ConsumerInterface $cn */
        //         $cn = $consumer[0];
        //
        //         $cn->register($consumer[1], $value, $fresh, $consumer[2]);
        //
        //         if ($key !== self::INSERT_ID) {
        //             // primary key is always delivered as fresh
        //             $fresh = false;
        //         }
        //     }
        // }

        $this->state->setStatus(Node::MANAGED);
        $this->state->setTransactionData($data);
        if ($insertID !== null && count($this->primaryKeys) === 1) {
            $this->state->setData([$this->primaryKeys[0] => $insertID]);
            $this->state->setTransactionData([$this->primaryKeys[0] => $insertID]);
            // $this->transactionData[$this->primaryKeys[0]] = $insertID;
            // $this->state->register($this->primaryKeys[0], $insertID);
        }

        parent::execute();
    }
}
