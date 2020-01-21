<?php

namespace Cycle\ORM\Select;

use Cycle\ORM\ORMInterface;
use Cycle\ORM\Select;
use Cycle\ORM\Transaction;

class PersistRepository extends Repository
{
    /** @var Transaction */
    private $transaction;

    /**
     * @param Select $select
     * @param ORMInterface $orm
     */
    public function __construct(
        Select $select,
        ORMInterface $orm
    ) {
        parent::__construct($select);
        $this->transaction = new Transaction($orm);
    }

    /**
     * @param mixed $entity
     * @param bool $cascade
     *
     * @throws \Throwable
     */
    public function save(
        $entity,
        bool $cascade = true
    ): void {
        $this->transaction->persist(
            $entity,
            $cascade ? Transaction::MODE_CASCADE : Transaction::MODE_ENTITY_ONLY
        );

        $this->transaction->run(); // transaction is clean after run
    }

    /**
     * @param mixed $entity
     * @param bool $cascade
     *
     * @throws \Throwable
     */
    public function delete(
        $entity,
        bool $cascade = true
    ): void {
        $this->transaction->persist(
            $entity,
            $cascade ? Transaction::MODE_CASCADE : Transaction::MODE_ENTITY_ONLY
        );

        $this->transaction->run(); // transaction is clean after run
    }

    /**
     * @param $id
     * @param bool $cascade
     *
     * @throws \Throwable
     */
    public function deleteByPK(
        $id,
        bool $cascade = true
    ): void {
        $this->delete($this->findByPK($id), $cascade);
    }
}
