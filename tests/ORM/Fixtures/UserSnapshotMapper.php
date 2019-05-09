<?php
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */
declare(strict_types=1);

namespace Cycle\ORM\Tests\Fixtures;

use Cycle\ORM\Command\Branch\ContextSequence;
use Cycle\ORM\Command\ContextCarrierInterface;
use Cycle\ORM\Command\Database\Insert;
use Cycle\ORM\Heap\Node;
use Cycle\ORM\Heap\State;
use Cycle\ORM\Mapper\Mapper;

class UserSnapshotMapper extends Mapper
{
    public function queueCreate($entity, Node $node, State $state): ContextCarrierInterface
    {
        $cc = parent::queueCreate($entity, $node, $state);

        $cs = new ContextSequence();
        $cs->addPrimary($cc);
        $cs->addCommand($this->snap($node, 'create', $cc));

        return $cs;
    }

    public function queueUpdate($entity, Node $node, State $state): ContextCarrierInterface
    {
        $cc = parent::queueUpdate($entity, $node, $state);

        $cs = new ContextSequence();
        $cs->addPrimary($cc);
        $cs->addCommand($this->snap($node, 'update', $cc));

        return $cs;
    }

    protected function snap(Node $node, string $action, ContextCarrierInterface $cc): Insert
    {
        $data = $node->getData();
        unset($data['id']);

        $snap = new Insert(
            $this->source->getDatabase(),
            'user_snapshots',
            $data + [
                'at'     => new \DateTimeImmutable(),
                'action' => $action
            ]
        );

        if ($cc instanceof Insert) {
            $snap->waitContext('user_id', true);
            $cc->forward(Insert::INSERT_ID, $snap, 'user_id');
        } else {
            $snap->register('user_id', $node->getData()['id'], true);
        }

        return $snap;
    }
}