<?php
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */
declare(strict_types=1);

namespace Spiral\Cycle\Tests\Fixtures;

use Spiral\Cycle\Command\ContextCarrierInterface;
use Spiral\Cycle\Command\Database\Update;
use Spiral\Cycle\Heap\State;
use Spiral\Cycle\Mapper\Mapper;

class TimestampedMapper extends Mapper
{
    public function queueCreate($entity, State $state): ContextCarrierInterface
    {
        $cmd = parent::queueCreate($entity, $state);

        $state->register('created_at', new \DateTimeImmutable(), true);
        $cmd->register('created_at', new \DateTimeImmutable(), true);

        $state->register('updated_at', new \DateTimeImmutable(), true);
        $cmd->register('updated_at', new \DateTimeImmutable(), true);

        return $cmd;
    }

    public function queueUpdate($entity, State $state): ContextCarrierInterface
    {
        /** @var Update $cmd */
        $cmd = parent::queueUpdate($entity, $state);

        if (!$cmd->isEmpty()) {
            $state->register('updated_at', new \DateTimeImmutable(), true);
            $cmd->register('updated_at', new \DateTimeImmutable(), true);
        }

        return $cmd;
    }
}