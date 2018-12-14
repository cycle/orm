<?php
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

namespace Spiral\Cycle\Command\Database;

use Spiral\Cycle\Command\ContextCarrierInterface;
use Spiral\Cycle\Command\DatabaseCommand;
use Spiral\Cycle\Command\Traits\ContextTrait;
use Spiral\Cycle\Command\Traits\ErrorTrait;
use Spiral\Cycle\Context\ConsumerInterface;
use Spiral\Cycle\Context\ProducerInterface;
use Spiral\Cycle\Exception\CommandException;
use Spiral\Database\DatabaseInterface;

/**
 * Insert data into associated table and provide lastInsertID promise.
 */
class Insert extends DatabaseCommand implements ContextCarrierInterface, ProducerInterface
{
    use ContextTrait, ErrorTrait;

    // Special identifier to forward insert key into
    public const INSERT_ID = '@lastInsertID';

    /** @var array */
    private $data;

    /** @var ConsumerInterface[] */
    protected $consumers = [];

    /**
     * @param DatabaseInterface $db
     * @param string            $table
     * @param array             $data
     */
    public function __construct(DatabaseInterface $db, string $table, array $data = [])
    {
        parent::__construct($db, $table);
        $this->data = $data;
    }

    /**
     * @inheritdoc
     */
    public function isReady(): bool
    {
        return empty($this->waitContext);
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
    ) {
        if ($trigger) {
            throw new CommandException("Insert command can only forward keys after the execution");
        }

        $this->consumers[$key][] = [$consumer, $target, $stream];
    }

    /**
     * @inheritdoc
     */
    public function register(string $key, $value, bool $fresh = false, int $stream = self::DATA)
    {
        if ($fresh || !is_null($value)) {
            $this->freeContext($key);
        }

        $this->setContext($key, $value);
    }

    /**
     * Insert values, context not included.
     *
     * @return array
     */
    public function getData(): array
    {
        return array_merge($this->data, $this->context);
    }

    /**
     * Insert data into associated table.
     */
    public function execute()
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