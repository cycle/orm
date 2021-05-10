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

    /** @var ConsumerInterface[][] */
    protected array $consumers = [];

    public function __construct(
        DatabaseInterface $db,
        string $table,
        array $data = [],
        array $primaryKeys = []
    ) {
        parent::__construct($db, $table);
        $this->data = $data;
        $this->primaryKeys = $primaryKeys;
    }

    public function isReady(): bool
    {
        return $this->waitContext === [];
    }

    /**
     * @inheritdoc
     *
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

        $this->consumers[$key][] = [$consumer, $target, $stream];
    }

    public function register(string $key, $value, bool $fresh = false, int $stream = self::DATA): void
    {
        if ($fresh || $value !== null) {
            $this->freeContext($key);
        }

        $this->setContext($key, $value);
    }

    /**
     * Insert values, context not included.
     */
    public function getData(): array
    {
        return array_merge($this->data, $this->context);
    }

    /**
     * Insert data into associated table.
     */
    public function execute(): void
    {
        $data = $this->getData();

        $insert = $this->db->insert($this->table)->values($data);
        if (count($this->primaryKeys) === 1 && $insert instanceof PostgresInsertQuery) {
            $insert->returning(current($this->primaryKeys));
        }

        $insertID = $insert->run();

        foreach ($this->consumers as $key => $consumers) {
            $fresh = true;
            if ($key === self::INSERT_ID) {
                $value = $insertID;
            } else {
                $value = $data[$key] ?? null;
            }

            foreach ($consumers as $id => $consumer) {
                /** @var ConsumerInterface $cn */
                $cn = $consumer[0];

                $cn->register($consumer[1], $value, $fresh, $consumer[2]);

                if ($key !== self::INSERT_ID) {
                    // primary key is always delivered as fresh
                    $fresh = false;
                }
            }
        }

        parent::execute();
    }
}
