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
    public function findByPK($value);

    public function findOne(array $where = []);

    public function findAll(array $where = []);
}