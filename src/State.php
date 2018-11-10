<?php
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

namespace Spiral\ORM;

use Spiral\ORM\Command\CommandPromiseInterface;

/**
 * Evil twin of every entity, contain meta information about entity data, primary key,
 * loaded relation and current heap state.
 */
final class State
{
    public const NEW              = 0;
    public const LOADED           = 1;
    public const SCHEDULED        = 2;
    public const SCHEDULED_UPDATE = 3;

    /** @var mixed */
    private $primaryKey;

    /** @var int */
    private $state;

    /** @var array */
    private $data;

    private $command;

    /**
     * @param mixed       $primaryKey
     * @param int         $state
     * @param array       $data
     */
    public function __construct($primaryKey, int $state, array $data)
    {
        $this->primaryKey = $primaryKey;
        $this->state = $state;
        $this->data = $data;
    }

    /**
     * @return mixed
     */
    public function getPrimaryKey()
    {
        return $this->primaryKey;
    }

    /**
     * @return int
     */
    public function getState(): int
    {
        return $this->state;
    }

    /**
     * @param int $state
     */
    public function setState(int $state): void
    {
        $this->state = $state;
    }

    /**
     * @return array
     */
    public function getData(): array
    {
        return $this->data;
    }

    public function setField(string $name, $value)
    {
        $this->data[$name] = $value;
    }

    public function setPrimaryKey(string $name, $value)
    {
        $this->primaryKey = $value;
        $this->data[$name] = $value;
    }

    public function setCommand(CommandPromiseInterface $commandPromise = null)
    {
        $this->command = $commandPromise;
    }

    // todo: store original set of relations (YEEEEAH BOYYYY)

    public function getCommandPromise(): ?CommandPromiseInterface
    {
        return $this->command;
    }
}