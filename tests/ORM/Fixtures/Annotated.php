<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Fixtures;

/**
 * @entity (role="annotated")
 * @table (database="default", table="annotated", indexes={@index (columns={"email"}, unique=true)})
 */
class Annotated
{
    /**
     * @column (type="primary", name="internal_id")
     *
     * @var int
     */
    protected $id;

    /**
     * @column (type="string(32)", name="email_str")
     *
     * @var string
     */
    protected $email;

    /**
     * @hasOne (target="user", nullable=true, cascade=true)
     *
     * @var User
     */
    protected $user;
}
