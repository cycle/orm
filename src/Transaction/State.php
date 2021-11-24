<?php

declare(strict_types=1);

namespace Cycle\ORM\Transaction;

use Cycle\ORM\Exception\SuccessTransactionRetryException;
use Cycle\ORM\EntityManagerInterface;

class State implements StateInterface
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private ?\Throwable $error = null
    ) {
    }

    public function isSuccess(): bool
    {
        return $this->error === null;
    }

    public function getError(): ?\Throwable
    {
        return $this->error;
    }

    public function retry(): static
    {
        if ($this->isSuccess()) {
            throw new SuccessTransactionRetryException('Successful transaction can not be retried.');
        }

        try {
            $this->entityManager->run();
        } catch (\Throwable $e) {
            return new static(clone $this->entityManager, $e);
        }

        return new static(clone $this->entityManager);
    }
}
