<?php
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

namespace Spiral\ORM\Command;

class DelayCommand implements CommandInterface, \IteratorAggregate
{
    private $parent;

    private $need;

    public function __construct(ContextualCommandInterface $command, array $need = [])
    {
        $this->parent = $command;
        $this->need = $need;
    }

    public function getIterator()
    {
        yield $this->parent;
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

    public function isReady(): bool
    {
        $data = $this->parent->getContext();

        foreach ($this->need as $key) {
            if (empty($data[$key])) {
                return true;
            }
        }

        return false;
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