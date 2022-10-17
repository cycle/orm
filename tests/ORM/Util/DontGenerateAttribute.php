<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Util;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS)]
final class DontGenerateAttribute
{
}
