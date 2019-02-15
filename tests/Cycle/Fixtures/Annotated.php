<?php
declare(strict_types=1);
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

namespace Spiral\Cycle\Tests\Fixtures;

/**
 * @entity (role="annotated")
 * @table (database="default",table="annotated", indexes={
 *   @index(columns={"email"}, unique = true)
 * })
 */
class Annotated
{
    /**
     * @column (type="primary", name="internal_id")
     * @var int
     */
    protected $id;

    /**
     * @column (type="string(32)", name="email_str")
     * @var string
     */
    protected $email;

    /**
     * @hasOne (target="user", nullable=true, cascade=true)
     * @var User
     */
    protected $user;
}