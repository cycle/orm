<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Functional\Driver\Common\Typecast\Fixture;

use Cycle\ORM\Parser\TypecastInterface;

class ParentTypecast implements TypecastInterface
{
    private bool $set = false;

    public function setRules(array $rules): array
    {
        if ($this->set) {
            throw new \Exception('ParentTypecast should be run only once');
        }

        $this->set = true;

        return $rules;
    }

    public function cast(array $values): array
    {
        return $values;
    }
}
