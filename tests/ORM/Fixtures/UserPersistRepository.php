<?php

// phpcs:ignoreFile
declare(strict_types=1);

namespace Cycle\ORM\Tests\Fixtures;

use Cycle\ORM\ORMInterface;
use Cycle\ORM\Select;
use Cycle\ORM\Select\Repository;
use Cycle\ORM\Transaction;

class UserPersistRepository extends Repository
{
    /** @var Transaction */
    private $transaction;

    public function __construct(Select $select, ORMInterface $orm)
    {
        parent::__construct($select);
        $this->transaction = new Transaction($orm);
    }

    /**
     *
     * @throws \Throwable
     */
    public function save(User $user, bool $cascade = true): void
    {
        $this->transaction->persist(
            $user,
            $cascade ? Transaction::MODE_CASCADE : Transaction::MODE_ENTITY_ONLY,
        );

        $this->transaction->run(); // transaction is clean after run
    }
}
