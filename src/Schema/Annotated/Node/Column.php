<?php
declare(strict_types=1);
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

namespace Spiral\Cycle\Schema\Annotated\Node;

use Spiral\Annotations\AbstractAnnotation;
use Spiral\Annotations\Parser;

class Column extends AbstractAnnotation
{
    protected const NAME   = 'column';
    protected const SCHEMA = [
        'type'     => Parser::STRING,
        'name'     => Parser::STRING,
        'nullable' => Parser::BOOL,
        'default'  => Parser::MIXED
    ];

    /** @var string */
    protected $type;

    /** @var string|null */
    protected $name;

    /** @var bool */
    protected $nullable;

    /** @var mixed */
    protected $default;

    /**
     * @return string
     */
    public function getType(): string
    {
        return $this->type;
    }

    /**
     * @return string|null
     */
    public function getInternalName(): ?string
    {
        return $this->name;
    }

    /**
     * @return bool
     */
    public function isNullable(): bool
    {
        return (bool)$this->nullable;
    }

    /**
     * @return mixed
     */
    public function getDefault()
    {
        return $this->default;
    }
}