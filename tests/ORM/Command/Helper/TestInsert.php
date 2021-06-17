<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Command\Helper;

use Cycle\ORM\Command\DatabaseCommand;
use Cycle\ORM\Command\Traits\ErrorTrait;
use Cycle\ORM\Context\ConsumerInterface;
use Cycle\ORM\Context\ProducerInterface;
use Cycle\ORM\Exception\CommandException;
use Spiral\Database\DatabaseInterface;

class TestInsert extends DatabaseCommand implements ProducerInterface, ConsumerInterface
{
    use ErrorTrait;

    // Special identifier to forward insert key into
    public const INSERT_ID = '@lastInsertID';

    /** @var array */
    protected $data;

    /** @var ConsumerInterface[] */
    protected $consumers = [];

    /**
     * @param DatabaseInterface $db
     * @param string            $table
     * @param array             $data
     */
    public function __construct(DatabaseInterface $db, string $table, array $data = [], callable $generateID = null)
    {
        parent::__construct($db, $table);
        $this->data = $data;
    }

    /**
     * @inheritdoc
     */
    public function isReady(): bool
    {
        return true;
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
            throw new CommandException('Insert command can only forward keys after the execution');
        }

        $this->consumers[$key][] = [$consumer, $target, $stream];
    }

    public function register(string $key, $value, bool $fresh = false, int $stream = self::DATA): void
    {
    }

    /**
     * Insert values, context not included.
     *
     * @return array
     */
    public function getData(): array
    {
        return $this->data;
    }

    /**
     * Insert data into associated table.
     */
    public function execute(): void
    {
        $update = true;
        $data = $this->getData();
        $insertID = $this->db->insert($this->table)->values($data)->run();

        foreach ($this->consumers as $key => $consumers) {
            foreach ($consumers as $id => $consumer) {
                /** @var ConsumerInterface $cn */
                $cn = $consumer[0];

                if ($key == self::INSERT_ID) {
                    $value = $insertID;
                } else {
                    $value = $data[$key] ?? null;
                }

                $cn->register($consumer[1], $value, $update, $consumer[2]);
                $update = false;
            }
        }

        parent::execute();
    }
}
