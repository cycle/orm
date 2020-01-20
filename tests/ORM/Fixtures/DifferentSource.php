<?php


namespace Cycle\ORM\Tests\Fixtures;


use Cycle\ORM\Select\ConstrainInterface;
use Cycle\ORM\Select\SourceInterface;
use Spiral\Database\DatabaseInterface;

class DifferentSource implements SourceInterface
{

    /**
     * @inheritDoc
     */
    public function getDatabase(): DatabaseInterface
    {
        // TODO: Implement getDatabase() method.
        return null;
    }

    /**
     * @inheritDoc
     */
    public function getTable(): string
    {
        return null;
    }

    /**
     * @inheritDoc
     */
    public function withConstrain(?ConstrainInterface $constrain): SourceInterface
    {
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function getConstrain(): ?ConstrainInterface
    {
        return null;
    }
}