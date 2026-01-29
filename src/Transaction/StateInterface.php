<?php

declare(strict_types=1);

namespace Cycle\ORM\Transaction;

use Cycle\ORM\Exception\SuccessTransactionRetryException;
use Throwable;

interface StateInterface
{
    /**
     * Check if transaction has been run successful.
     *
     * @psalm-assert-if-true null $this->getLastError()
     *
     * @psalm-assert-if-false Throwable $this->getLastError()
     */
    public function isSuccess(): bool;

    /**
     * The reason of failed transaction.
     */
    public function getLastError(): ?\Throwable;

    /**
     * Try to rerun transaction if previous run has been failed.
     *
     * @throws SuccessTransactionRetryException
     */
    public function retry(): self;
}
