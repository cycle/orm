<?php
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */
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

    /**
     * @param Select       $select
     * @param ORMInterface $orm
     */
    public function __construct(Select $select, ORMInterface $orm)
    {
        parent::__construct($select);
        $this->transaction = new Transaction($orm);
    }

    /**
     * @param User $user
     * @param bool $cascade
     *
     * @throws \Throwable
     */
    public function save(User $user, bool $cascade = true)
    {
        $this->transaction->persist(
            $user,
            $cascade ? Transaction::MODE_CASCADE : Transaction::MODE_ENTITY_ONLY
        );

        $this->transaction->run(); // transaction is clean after run
    }
}