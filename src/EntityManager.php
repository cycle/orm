<?php

declare(strict_types=1);

namespace Cycle\ORM;

use Cycle\ORM\Transaction\RunnerInterface;
use Cycle\ORM\Transaction\StateInterface;
use Cycle\ORM\Transaction\UnitOfWork;

class EntityManager implements EntityManagerInterface
{
    private UnitOfWork $unitOfWork;

    public function __construct(private ORMInterface $orm)
    {
        $this->clean();
    }

    public function persistState(object $entity, bool $cascade = true): static
    {
        $this->unitOfWork->persistState($entity, $cascade);

        return $this;
    }

    public function persist(object $entity, bool $cascade = true): static
    {
        $this->unitOfWork->persistDeferred($entity, $cascade);

        return $this;
    }

    public function delete(object $entity, bool $cascade = true): static
    {
        $this->unitOfWork->delete($entity, $cascade);

        return $this;
    }

    public function run(bool $throwException = true, ?RunnerInterface $runner = null): StateInterface
    {
        if ($runner !== null) {
            $this->unitOfWork->setRunner($runner);
        }

        $state = $this->unitOfWork->run();
        $this->clean();

        if ($throwException && !$state->isSuccess()) {
            throw $state->getLastError();
        }

        return $state;
    }

    public function clean(bool $cleanHeap = false): static
    {
        $this->unitOfWork = new UnitOfWork($this->orm);
        $cleanHeap && $this->orm->getHeap()->clean();

        return $this;
    }
}
