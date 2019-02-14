<?php
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */
declare(strict_types=1);

namespace Spiral\Cycle\Schema;

interface EntityInterface
{
    /**
     * @return string
     */
    public function getRole(): string;

    /**
     * @return string
     */
    public function getMapper(): string;

    /**
     * @return string
     */
    public function getSource(): string;

    /**
     * @return string
     */
    public function getRepository(): string;

    /**
     * @return string
     */
    public function getTable(): string;

    /**
     * @return string|null
     */
    public function getDatabase(): ?string;
}