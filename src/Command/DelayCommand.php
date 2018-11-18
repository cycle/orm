<?php
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

namespace Spiral\ORM\Command;

class DelayCommand implements DelayedCommandInterface
{
    private $parent;

    public function __construct(ContextCommandInterface $command)
    {
        $this->parent = $command;
    }

    public function execute()
    {
        $this->parent->execute();
    }

    public function complete()
    {
        $this->parent->complete();
    }

    public function rollBack()
    {
        $this->parent->rollBack();
    }

    public function onExecute(callable $closure)
    {
        $this->parent->onExecute($closure);
    }

    public function onComplete(callable $closure)
    {
        $this->parent->onComplete($closure);
    }

    public function onRollBack(callable $closure)
    {
        $this->parent->onRollBack($closure);
    }

    public function getParent()
    {
        return $this->parent;
    }

    public function getContext(): array
    {
        return $this->parent->getContext();
    }

    public function setContext(string $name, $value)
    {
        // todo: if value null?
        return $this->parent->setContext($name, $value);
    }

    public function isDelayed(): bool
    {
        return $this->parent->isEmpty();
    }

    private $description;

    public function setDescription(string $description)
    {
        $this->description = $description;
    }

    public function __toString(): string
    {
        return $this->description;
    }
}