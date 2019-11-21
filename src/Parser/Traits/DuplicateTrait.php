<?php

/**
 * Cycle DataMapper ORM
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

declare(strict_types=1);

namespace Cycle\ORM\Parser\Traits;

/**
 * Trait provides ability for Node to ensure that given data is unique in selection. Primary key
 * would be used to tie duplicate nodes together.
 */
trait DuplicateTrait
{
    /** @var string @internal */
    protected $duplicateCriteria = null;

    /** @var array @internal */
    protected $duplicates = [];

    /**
     * @param string $column
     */
    protected function setDuplicateCriteria(string $column): void
    {
        $this->duplicateCriteria = $column;
    }

    /**
     * In many cases (for example if you have inload of HAS_MANY relation) record data can be
     * replicated by many result rows (duplicated). To ensure proper data links we have to
     * deduplicate such records. This method use reference based feedback loop.
     *
     * @param array $data Reference to parsed record data, reference will be pointed to valid and
     *                    existed data segment if such data was already parsed.
     * @return bool Return true if data is unique.
     */
    final protected function deduplicate(array &$data): bool
    {
        if ($this->duplicateCriteria === null) {
            return true;
        }

        $criteria = $this->duplicateCriteria($data);

        if (isset($this->duplicates[$criteria])) {
            // duplicate is presented, let's reduplicate
            $data = $this->duplicates[$criteria];

            return false;
        }

        // remember record to prevent future duplicates
        $this->duplicates[$criteria] = &$data;

        return true;
    }

    /**
     * Calculate duplication criteria.
     *
     * @param array $data
     * @return string
     */
    protected function duplicateCriteria(array &$data): string
    {
        return (string)$data[$this->duplicateCriteria];
    }
}
