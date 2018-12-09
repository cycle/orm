<?php
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

namespace Spiral\ORM;

/**
 * Defines ability to locate entities based on scope parameters.
 */
interface RepositoryInterface
{
    // OrderBy options.
    const SORT_ASC  = 'ASC';
    const SORT_DESC = 'DESC';

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
     * @param array $orderBy
     * @return iterable
     */
    public function findAll(array $scope = [], array $orderBy = []): iterable;
}