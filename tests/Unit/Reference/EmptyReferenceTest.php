<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Unit\Reference;

use Cycle\ORM\Reference\EmptyReference;
use Testo\Assert;
use Testo\Expect;
use Testo\Test;

#[Test]
final class EmptyReferenceTest
{
    public function isResolvedWithGivenValue(): void
    {
        $reference = new EmptyReference('user', null);

        Assert::same($reference->getRole(), 'user');
        Assert::true($reference->hasValue());
        Assert::null($reference->getValue());
    }

    public function keepsNewValue(): void
    {
        $reference = new EmptyReference('user', null);
        $user = new \stdClass();

        $reference->setValue($user);

        Assert::same($reference->getValue(), $user);
    }

    public function hasNoScope(): never
    {
        $reference = new EmptyReference('user', null);

        Expect::exception(\RuntimeException::class);

        $reference->getScope();
    }
}
