<?php

declare(strict_types=1);

namespace Cycle\ORM\Command\Database;

use Cycle\ORM\Command\DatabaseCommand;
use Cycle\ORM\Command\Traits\ErrorTrait;
use Cycle\ORM\Command\StoreCommandInterface;
use Cycle\ORM\Context\ConsumerInterface;
use Cycle\ORM\Heap\Node;
use Cycle\ORM\Heap\State;
use Spiral\Database\DatabaseInterface;
use Spiral\Database\Driver\Postgres\Query\PostgresInsertQuery;

/**
 * Insert data into associated table and provide lastInsertID promise.
 */
final class Insert extends DatabaseCommand implements StoreCommandInterface, ConsumerInterface
{
    use ErrorTrait;

    /**
     * Special identifier to forward insert key into
     */
    public const INSERT_ID = '@lastInsertID';

    protected array $appendix = [];

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
        return true;
    }

    public function hasData(): bool
    {
        return count($this->appendix) > 0 || count($this->state->getData()) > 0;
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

        // filter PK null values
        foreach ($this->primaryKeys as $key) {
            if (!isset($data[$key])) {
                unset($data[$key]);
            }
        }

        $insert = $this->db
            ->insert($this->table)
            ->values(($this->mapper === null ? $data : ($this->mapper)($data)) + $this->appendix);
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
        $this->state->updateTransactionData();
        if (count($this->primaryKeys) > 0) {
            $fpk = $this->primaryKeys[0]; // first PK
            if ($insertID !== null && count($this->primaryKeys) === 1 && !isset($data[$fpk])) {
                $this->state->register($fpk, $insertID);
                $this->state->updateTransactionData();
                // $this->transactionData[$this->primaryKeys[0]] = $insertID;
                // $this->state->register($fpk, $insertID);
            }
        }

        parent::execute();
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
