<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Functional\Driver\Common\Transaction;

use Cycle\ORM\Exception\RunnerException;
use Cycle\ORM\Tests\Fixtures\TestCommand;
use Cycle\ORM\Tests\Functional\Driver\Common\BaseTest;
use Cycle\ORM\Tests\Traits\TableTrait;
use Cycle\ORM\Transaction\Runner;

abstract class RunnerTest extends BaseTest
{
    use TableTrait;

    public function setUp(): void
    {
        parent::setUp();
    }

    public function testRunOpenTransaction(): void
    {
        $this->assertSame(0, $this->getDriver()->getTransactionLevel());

        Runner::openTransaction()->run(new TestCommand($this->getDatabase()));

        $this->assertSame(1, $this->getDriver()->getTransactionLevel());
    }

    public function testRunContinueTransaction(): void
    {
        $this->getDriver()->beginTransaction();

        $this->assertSame(1, $this->getDriver()->getTransactionLevel());

        Runner::continueTransaction()->run(new TestCommand($this->getDatabase()));

        $this->assertSame(1, $this->getDriver()->getTransactionLevel());
    }

    public function testRunContinueNotOpenedTransaction(): void
    {
        $this->expectException(RunnerException::class);

        Runner::continueTransaction()->run(new TestCommand($this->getDatabase()));
    }

    public function testRunIgnoreTransaction(): void
    {
        $this->assertSame(0, $this->getDriver()->getTransactionLevel());

        (Runner::ignoreTransaction())->run(new TestCommand($this->getDatabase()));

        $this->assertSame(0, $this->getDriver()->getTransactionLevel());
    }

    public function testCompleteCloseTransaction(): void
    {
        $runner = Runner::openTransaction();
        $runner->run(new TestCommand($this->getDatabase()));

        $this->assertSame(1, $this->getDriver()->getTransactionLevel());

        $runner->complete();

        $this->assertSame(0, $this->getDriver()->getTransactionLevel());
    }

    public function testCompleteContinueTransaction(): void
    {
        $this->getDriver()->beginTransaction();

        $this->assertSame(1, $this->getDriver()->getTransactionLevel());

        $runner = Runner::continueTransaction();
        $runner->run(new TestCommand($this->getDatabase()));
        $runner->complete();

        $this->assertSame(1, $this->getDriver()->getTransactionLevel());
    }

    public function testCompleteIgnoreTransaction(): void
    {
        $this->getDriver()->beginTransaction();
        $this->assertSame(1, $this->getDriver()->getTransactionLevel());

        $runner = Runner::ignoreTransaction();
        $runner->run(new TestCommand($this->getDatabase()));
        $runner->complete();

        $this->assertSame(1, $this->getDriver()->getTransactionLevel());
    }

    public function testRollbackCloseTransaction(): void
    {
        $runner = Runner::openTransaction();
        $runner->run(new TestCommand($this->getDatabase()));

        $this->assertSame(1, $this->getDriver()->getTransactionLevel());

        $runner->rollback();

        $this->assertSame(0, $this->getDriver()->getTransactionLevel());
    }

    public function testRollbackContinueTransaction(): void
    {
        $this->getDriver()->beginTransaction();

        $this->assertSame(1, $this->getDriver()->getTransactionLevel());

        $runner = Runner::continueTransaction();
        $runner->run(new TestCommand($this->getDatabase()));
        $runner->rollback();

        $this->assertSame(1, $this->getDriver()->getTransactionLevel());
    }

    public function testRollbackIgnoreTransaction(): void
    {
        $this->getDriver()->beginTransaction();
        $this->assertSame(1, $this->getDriver()->getTransactionLevel());

        $runner = Runner::ignoreTransaction();
        $runner->run(new TestCommand($this->getDatabase()));
        $runner->rollback();

        $this->assertSame(1, $this->getDriver()->getTransactionLevel());
    }
}
