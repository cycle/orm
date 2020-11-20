<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Fixtures;

use Cycle\ORM\Command\ContextCarrierInterface;
use Cycle\ORM\Heap\Node;
use Cycle\ORM\Heap\State;
use Cycle\ORM\Mapper\Mapper;
use Spiral\Database\Injection\Fragment;

class SequenceDefaultValueMapper extends Mapper
{
    public function queueCreate($entity, Node $node, State $state): ContextCarrierInterface
    {
        $command = parent::queueCreate($entity, $node, $state);

        $command->register('user_code', new Fragment('nextval(\'user_code_seq\')'), true);
        //$state->forward('user_code', $command, 'user_code');

        return $command;
    }
}
