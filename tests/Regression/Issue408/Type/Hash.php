<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Regression\Issue408\Type;

class Hash implements ValueObjectInterface
{
    use StringTrait;
}
