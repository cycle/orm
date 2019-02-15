<?php
declare(strict_types=1);
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

namespace Spiral\Cycle\Schema\Annotated\Annotation;

use Spiral\Annotations\AbstractAnnotation;
use Spiral\Annotations\Parser;

class Entity extends AbstractAnnotation
{
    protected const NAME   = 'entity';
    protected const SCHEMA = [
        'role'       => Parser::STRING,
        'mapper'     => Parser::STRING,
        'repository' => Parser::STRING,
        'source'     => Parser::STRING
    ];

    /** @var string|null */
    protected $role;

    /** @var string|null */
    protected $mapper;

    /** @var string|null */
    protected $repository;

    /** @var string|null */
    protected $source;

    /**
     * @return string
     */
    public function getRole(): ?string
    {
        return $this->role;
    }

    /**
     * @return string
     */
    public function getMapper(): ?string
    {
        return $this->mapper;
    }

    /**
     * @return string
     */
    public function getRepository(): ?string
    {
        return $this->repository;
    }

    /**
     * @return string
     */
    public function getSource(): ?string
    {
        return $this->source;
    }
}