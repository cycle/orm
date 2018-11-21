<?php
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

namespace Spiral\ORM\Command\Control;

use Spiral\ORM\Command\ContextualInterface;

/**
 * Control command to defer execution of parent command until needed context values are set.
 */
class Defer implements ContextualInterface, \IteratorAggregate
{
    /** @var ContextualInterface */
    private $command;

    /** @var array */
    private $require = [];

    /** @var string */
    private $description;

    /**
     * @param ContextualInterface $command
     * @param array               $require
     * @param string              $description
     */
    public function __construct(ContextualInterface $command, array $require = [], string $description = '')
    {
        $this->command = $command;
        $this->require = array_flip($require);
        $this->description = $description;
    }

    /**
     * Required to display error when values can not be satisfied.
     *
     * @return string
     */
    public function __toString(): string
    {
        return $this->description;
    }

    /**
     * @return \Generator
     */
    public function getIterator()
    {
        if (!$this->isReady()) {
            yield $this;

            return;
        }

        yield $this->command;
    }

    /**
     * Delayed until all context values are resolved.
     *
     * @return bool
     */
    public function isReady(): bool
    {
        return empty($this->require);
    }

    /**
     * @inheritdoc
     */
    public function getContext(): array
    {
        return $this->command->getContext();
    }

    /**
     * @inheritdoc
     */
    public function setContext(string $name, $value)
    {
        if (array_key_exists($name, $this->require)) {
            if (is_null($value)) {
                return;
            }

            unset($this->require[$name]);
        }

        $this->command->setContext($name, $value);
    }

    /**
     * @inheritdoc
     */
    public function execute()
    {
        // delegated to parent
    }

    /**
     * @inheritdoc
     */
    public function complete()
    {
        // delegated to parent
    }

    /**
     * @inheritdoc
     */
    public function rollBack()
    {
        // delegated to parent
    }

    /**
     * @inheritdoc
     */
    public function onExecute(callable $closure)
    {
        $this->command->onExecute($closure);
    }

    /**
     * @inheritdoc
     */
    public function onComplete(callable $closure)
    {
        $this->command->onComplete($closure);
    }

    /**
     * @inheritdoc
     */
    public function onRollBack(callable $closure)
    {
        $this->command->onRollBack($closure);
    }
}