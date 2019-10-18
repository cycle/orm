<?php

/**
 * Cycle DataMapper ORM
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

declare(strict_types=1);

namespace Cycle\ORM;

/**
 * Defines ability to locate entities based on scope parameters.
 */
interface RepositoryInterface
{
    /**
     * Find entity by the primary key value or return null.
     *
     * @param mixed $id
     * @return null|object
     */
    public function findByPK($id);

    /**
     * Find entity using given scope (where).
     *
     * @param array $scope
     * @return null|object
     */
    public function findOne(array $scope = []);

    /**
     * Find multiple entities using given scope and sort options.
     *
     * @param array $scope
     * @return iterable
     */
    public function findAll(array $scope = []): iterable;
}
