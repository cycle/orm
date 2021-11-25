<?php

declare(strict_types=1);

namespace Cycle\ORM\Transaction;

use Cycle\ORM\Exception\SuccessTransactionRetryException;

class SuccessfulState implements StateInterface
{
    public function isSuccess(): bool
    {
        return true;
    }

    public function getError(): ?\Throwable
    {
        return null;
    }

    public function retry(): static
    {
        throw new SuccessTransactionRetryException('Successful transaction can not be retried.');
    }
}
