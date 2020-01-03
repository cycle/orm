<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Fixtures;

use Cycle\ORM\RepositoryInterface;

class CorrectRepository implements RepositoryInterface
{

    /**
     * @inheritDoc
     */
    public function findByPK($id)
    {
        return [];
    }

    /**
     * @inheritDoc
     */
    public function findOne(array $scope = [])
    {
        return [];
    }

    /**
     * @inheritDoc
     */
    public function findAll(array $scope = []): iterable
    {
        return [];
    }
}
