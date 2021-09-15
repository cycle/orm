<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Fixtures;

use Cycle\ORM\Command\Database\Delete;
use Cycle\ORM\Command\Database\Update;
use Cycle\ORM\Command\ScopeCarrierInterface;
use Cycle\ORM\Command\Special\WrappedCommand;
use Cycle\ORM\Command\CommandInterface;
use Cycle\ORM\Heap\Node;
use Cycle\ORM\Heap\State;
use Cycle\ORM\Mapper\Mapper;

class OptimisticLockMapper extends Mapper
{
    private string $lockField = 'lock';

    public function queueUpdate($entity, Node $node, State $state): CommandInterface
    {
        /** @var Update $cc */
        $cc = parent::queueUpdate($entity, $node, $state);

        return $this->lock($node, $cc);
    }

    public function queueDelete($entity, Node $node, State $state): CommandInterface
    {
        /** @var Delete $cc */
        $cc = parent::queueDelete($entity, $node, $state);

        return $this->lock($node, $cc);
    }

    protected function lock(Node $node, Update|Delete $command): WrappedCommand
    {
        $scopeValue = $node->getInitialData()[$this->lockField] ?? null;
        if ($scopeValue === null) {
            throw new \RuntimeException(sprintf('The `%s` field is not set.', $this->lockField));
        }

        if ($command instanceof Update && $node->getData()[$this->lockField] === $scopeValue) {
            $command->register($this->lockField, $this->getLockingValue($node));
        }

        $command->setScope($this->lockField, $scopeValue);

        return WrappedCommand::wrapCommand($command)
            ->withAfterExecution(static function (ScopeCarrierInterface $command) use ($node): void {
                if ($command->getAffectedRows() === 0) {
                    throw new \RuntimeException(sprintf(
                        'The `%s` record is locked.',
                        $node->getRole()
                    ));
                }
            });
    }

    private function getLockingValue(Node $node): string
    {
        return microtime();
    }
}
