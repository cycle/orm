<?php
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

namespace Spiral\ORM\Command\Control;

use Spiral\ORM\Command\CarrierInterface;
use Spiral\ORM\Exception\CommandException;

/**
 * Wraps the sequence with commands and provides an ability to mock access to the primary command.
 */
class PrimarySequence extends Sequence implements CarrierInterface
{
    /**
     * @invisible
     * @var CarrierInterface
     */
    private $primary;

    /**
     * Add primary command to the sequence.
     *
     * @param CarrierInterface $command
     */
    public function addPrimary(CarrierInterface $command)
    {
        $this->addCommand($command);
        $this->primary = $command;
    }

    /**
     * @return CarrierInterface
     */
    public function getPrimary(): CarrierInterface
    {
        if (empty($this->primary)) {
            throw new CommandException("Primary sequence command is not set");
        }

        return $this->primary;
    }

    /**
     * {@inheritdoc}
     */
    public function waitContext(string $key, bool $required = true)
    {
        return $this->getPrimary()->waitContext($key, $required);
    }

    /**
     * {@inheritdoc}
     */
    public function setContext(string $key, $value)
    {
        $this->getPrimary()->setContext($key, $value);
    }

    /**
     * {@inheritdoc}
     */
    public function getContext(): array
    {
        return $this->getPrimary()->getContext();
    }

    /**
     * @inheritdoc
     */
    public function push(string $key, $value, bool $update = false, int $stream = self::DATA)
    {
        $this->getPrimary()->push($key, $value, $update, $stream);
    }

    /**
     * Closure to be called after command executing.
     *
     * @param callable $closure
     */
    public function onExecute(callable $closure)
    {
        $this->getPrimary()->onExecute($closure);
    }

    /**
     * To be called after parent transaction been commited.
     *
     * @param callable $closure
     */
    public function onComplete(callable $closure)
    {
        $this->getPrimary()->onComplete($closure);
    }

    /**
     * To be called after parent transaction been rolled back.
     *
     * @param callable $closure
     */
    public function onRollBack(callable $closure)
    {
        $this->getPrimary()->onRollBack($closure);
    }
}