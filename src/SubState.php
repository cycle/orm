<?php
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

namespace Spiral\ORM;


class SubState
{
    protected $context;

    protected $listeners;

    protected $lastCommand;

    public function __construct()
    {
        $this->context = [];
        $this->listeners = [];
        $this->lastCommand = null;
    }
}