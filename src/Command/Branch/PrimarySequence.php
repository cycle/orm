<?php
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

namespace Spiral\ORM\Command\Branch;

use Spiral\ORM\Command\CarrierInterface;
use Spiral\ORM\Exception\CommandException;

/**
 * Wraps the sequence with commands and provides an ability to mock access to the primary command.
 */
class PrimarySequence extends Sequence implements CarrierInterface
{
    /** @var CarrierInterface */
    protected $primary;

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
}