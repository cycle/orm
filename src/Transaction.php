<?php

declare(strict_types=1);

namespace Cycle\ORM;

use Cycle\ORM\Transaction\RunnerInterface;
use Cycle\ORM\Transaction\UnitOfWork;

/**
 * Transaction provides ability to define set of entities to be stored or deleted within one transaction. Transaction
 * can operate as UnitOfWork. Multiple transactions can co-exists in one application.
 *
 * Internally, upon "run", transaction will request mappers to generate graph of linked commands to create, update or
 * delete entities.
 *
 * @deprecated since 2.0 use {@see \Cycle\ORM\EntityManager}
 */
final class Transaction implements TransactionInterface
{
    private ?UnitOfWork $uow = null;

    public function __construct(
        private ORMInterface $orm,
        private ?RunnerInterface $runner = null,
    ) {}

    public function persist(object $entity, int $mode = self::MODE_CASCADE): self
    {
        $this->initUow()->persistDeferred($entity, $mode === self::MODE_CASCADE);

        return $this;
    }

    public function delete(object $entity, int $mode = self::MODE_CASCADE): self
    {
        $this->initUow()->delete($entity, $mode === self::MODE_CASCADE);

        return $this;
    }

    public function run(): void
    {
        if ($this->uow === null) {
            return;
        }
        $uow = $this->uow->run();
        $this->uow = null;

        if (!$uow->isSuccess()) {
            throw $uow->getLastError();
        }
    }

    private function initUow(): UnitOfWork
    {
        $this->uow ??= new UnitOfWork($this->orm, $this->runner);
        return $this->uow;
    }
}
