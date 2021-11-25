<?php

declare(strict_types=1);

namespace Cycle\ORM;

use Cycle\ORM\Transaction\Runner;
use Cycle\ORM\Transaction\RunnerInterface;
use Cycle\ORM\Transaction\StateInterface;
use Cycle\ORM\Transaction\UnitOfWork;

class EntityManager implements EntityManagerInterface
{
    private UnitOfWork $unitOfWork;
    private RunnerInterface $runner;

    public function __construct(
        private ORMInterface $orm,
        ?RunnerInterface $runner = null
    ) {
        $this->runner = $runner ?? new Runner();
        $this->clean();
    }

    public function persist(object $entity, bool $cascade = true): static
    {
        $this->unitOfWork->persist($entity, $cascade);

        return $this;
    }

    public function persistDeferred(object $entity, bool $cascade = true): static
    {
        // TODO: Implement persistDeferred() method.
    }

    public function delete(object $entity, bool $cascade = true): static
    {
        $this->unitOfWork->delete($entity, $cascade);

        return $this;
    }

    public function run(): StateInterface
    {
        $state = $this->unitOfWork->run();
        $this->clean();

        return $state;
    }

    private function clean()
    {
        $this->unitOfWork = new UnitOfWork($this->orm, $this->runner);
    }
}
