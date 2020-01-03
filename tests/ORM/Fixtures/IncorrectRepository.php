<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Fixtures;

class IncorrectRepository
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
