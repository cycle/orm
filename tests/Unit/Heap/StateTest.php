<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Unit\Heap;

use Cycle\ORM\Heap\Node;
use Cycle\ORM\Heap\State;
use Testo\Assert;
use Testo\Test;

#[Test]
final class StateTest
{
    public function clearStorageOfOneTypeKeepsOthers(): void
    {
        $state = new State(Node::MANAGED, []);
        $state->addToStorage('a', new State(Node::NEW, []));
        $state->addToStorage('b', new State(Node::NEW, []));

        $state->clearStorage('a');

        Assert::same($state->getStorage('a'), []);
        Assert::count($state->getStorage('b'), 1);
    }

    public function clearStorageWithoutTypeClearsAll(): void
    {
        $state = new State(Node::MANAGED, []);
        $state->addToStorage('a', new State(Node::NEW, []));
        $state->addToStorage('b', new State(Node::NEW, []));

        $state->clearStorage();

        Assert::same($state->getStorage('a'), []);
        Assert::same($state->getStorage('b'), []);
    }

    public function updatingChangedFieldsMakesStateManaged(): void
    {
        $state = new State(Node::SCHEDULED_UPDATE, ['a' => 1, 'b' => 2]);
        $state->register('a', 10);

        $state->updateTransactionData(['a']);

        Assert::same($state->getStatus(), Node::MANAGED);
        Assert::same($state->getTransactionData(), ['a' => 10, 'b' => 2]);
    }

    public function stateKeepsStatusWhileOtherFieldsAreChanged(): void
    {
        $state = new State(Node::SCHEDULED_UPDATE, ['a' => 1, 'b' => 2, 'c' => 3]);
        $state->register('a', 10);
        $state->register('c', 30);

        $state->updateTransactionData(['a']);

        Assert::same($state->getStatus(), Node::SCHEDULED_UPDATE);
        Assert::same($state->getChanges(), ['c' => 30]);
    }

    public function updatedObjectFieldIsComparedBySnapshot(): void
    {
        $date = new \DateTime('2020-01-01');
        $state = new State(Node::SCHEDULED_UPDATE, ['date' => $date], ['date' => new \DateTimeImmutable('2020-01-01')]);
        $state->register('date', new \DateTime('2021-01-01'));

        $state->updateTransactionData(['date']);

        Assert::same($state->getChanges(), []);
        Assert::false($state->hasChanges());
    }

    public function objectMutatedInPlaceCountsAsChange(): void
    {
        $date = new \DateTime('2020-01-01');
        $state = new State(
            Node::SCHEDULED_UPDATE,
            ['date' => $date, 'x' => 1],
            ['date' => new \DateTimeImmutable('2020-01-01')],
        );
        $date->modify('+1 day');
        $state->register('x', 2);

        $state->updateTransactionData(['x']);

        Assert::same($state->getStatus(), Node::SCHEDULED_UPDATE);
        Assert::same(\array_keys($state->getChanges()), ['date']);
    }

    public function newStateReportsAllDataAsChanges(): void
    {
        $state = new State(Node::NEW, ['a' => 1, 'b' => 2]);

        $state->updateTransactionData(['a']);

        Assert::same($state->getStatus(), Node::NEW);
        Assert::same($state->getChanges(), ['a' => 1, 'b' => 2]);
    }

    public function valueIsReadFromCurrentDataFirst(): void
    {
        $state = new State(Node::MANAGED, ['a' => 1]);
        $state->register('a', 2);

        Assert::same($state->getValue('a'), 2);
        Assert::null($state->getValue('missing'));
    }

    public function valueFallsBackToTransactionData(): void
    {
        $state = new State(Node::MANAGED, ['a' => 1, 'b' => 2]);
        $state->setRelation('b', null);

        Assert::same($state->getValue('b'), 2);
    }

    public function nullValueCountsOnlyWhenAllowed(): void
    {
        $state = new State(Node::MANAGED, ['a' => 1, 'b' => null, 'c' => 3, 'd' => null]);
        $state->setRelation('c', null);
        $state->setRelation('d', null);

        Assert::true($state->hasValue('a'));
        Assert::true($state->hasValue('b'));
        Assert::false($state->hasValue('b', false));
        Assert::true($state->hasValue('c', false));
        Assert::true($state->hasValue('d'));
        Assert::false($state->hasValue('d', false));
        Assert::false($state->hasValue('missing'));
        Assert::false($state->hasValue('missing', false));
    }

    public function registeringValueReleasesWaitingField(): void
    {
        $state = new State(Node::NEW, []);
        $state->waitField('a');

        $state->register('a', 1);

        Assert::true($state->isReady());
        Assert::same($state->getWaitingFields(), []);
    }

    public function requiredWaitingField(): void
    {
        $state = new State(Node::NEW, []);

        $state->waitField('a');

        Assert::same($state->getWaitingFields(), ['a']);
        Assert::same($state->getWaitingFields(true), ['a']);
        Assert::false($state->isReady());
    }

    public function optionalWaitingField(): void
    {
        $state = new State(Node::NEW, []);

        $state->waitField('a', false);

        Assert::same($state->getWaitingFields(), ['a']);
        Assert::same($state->getWaitingFields(true), []);
    }

    public function optionalWaitDoesNotWeakenRequiredOne(): void
    {
        $state = new State(Node::NEW, []);
        $state->waitField('a');
        $state->waitField('b', false);

        $state->waitField('a', false);
        $state->waitField('b', false);

        Assert::same($state->getWaitingFields(true), ['a']);
        Assert::same($state->getWaitingFields(), ['a', 'b']);
    }

    public function fieldWithValueIsNotRequired(): void
    {
        $state = new State(Node::NEW, ['a' => 1]);

        $state->waitField('a');

        Assert::same($state->getWaitingFields(true), []);
        Assert::same($state->getWaitingFields(), ['a']);
    }
}
