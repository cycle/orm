<?php
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

namespace Spiral\ORM;

interface RepositoryInterface
{
    public function findByPK($id);

    public function findOne(array $scope = []);

    // todo: order bY?
    public function findAll(array $scope = []): iterable;
}