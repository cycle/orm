<?php
/**
 * Spiral, Core Components
 *
 * @author Wolfy-J
 */

namespace Spiral\ORM\Command;

/**
 * Provides support for command events.
 */
abstract class AbstractCommand implements CommandInterface
{
    /** @var bool */
    private $executed = false;

    /**
     * {@inheritdoc}
     */
    public function isExecuted(): bool
    {
        return $this->executed;
    }

    /**
     * {@inheritdoc}
     */
    public function execute()
    {
        $this->executed = true;
    }

    /**
     * {@inheritdoc}
     */
    public function complete()
    {
        // nothing to do
    }

    /**
     * {@inheritdoc}
     */
    public function rollBack()
    {
        // nothing to do
    }
}