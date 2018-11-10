<?php
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

namespace Spiral\ORM;

interface RelationInterface
{
    public const TYPE   = 0;
    public const TARGET = 1;
    public const SCHEMA = 2;
}