<?php

declare(strict_types=1);

namespace Cycle\ORM\Transaction;

use Cycle\ORM\Exception\SuccessTransactionRetryException;

interface StateInterface
{
    /**
     * Check if transaction has been run successful.
     */
    public function isSuccess(): bool;

    /**
     * The reason of failed transaction.
     *
     * @return \Throwable|null
     */
    public function getError(): ?\Throwable;

    /**
     * Try to rerun transaction if previous run has been failed.
     *
     * @throws SuccessTransactionRetryException
     */
    public function retry(): self;
}
