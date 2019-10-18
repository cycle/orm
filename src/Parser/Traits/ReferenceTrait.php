<?php

/**
 * Cycle DataMapper ORM
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

declare(strict_types=1);

namespace Cycle\ORM\Parser\Traits;

use Cycle\ORM\Exception\ParserException;

/**
 * Mounts given data piece into reference based data tree.
 */
trait ReferenceTrait
{
    /**
     * Tree parts associated with reference keys and key values:
     * $this->collectedReferences[id][ID_VALUE] = [ITEM1, ITEM2, ...].
     *
     * @internal
     * @var array
     */
    protected $references = [];

    /**
     * Set of keys to be aggregated by Parser while parsing results.
     *
     * @internal
     * @var array
     */
    protected $trackReferences = [];

    /**
     * Mount record data into internal data storage under specified container using reference key
     * (inner key) and reference criteria (outer key value).
     *
     * Example (default ORM Loaders):
     * $this->parent->mount('profile', 'id', 1, [
     *      'id' => 100,
     *      'user_id' => 1,
     *      ...
     * ]);
     *
     * In this example "id" argument is inner key of "user" record and it's linked to outer key
     * "user_id" in "profile" record, which defines reference criteria as 1.
     *
     * Attention, data WILL be referenced to new memory location!
     *
     * @param string $container
     * @param string $key
     * @param mixed  $criteria
     * @param array  $data
     *
     * @throws ParserException
     */
    final protected function mount(string $container, string $key, $criteria, array &$data): void
    {
        if ($criteria === self::LAST_REFERENCE) {
            end($this->references[$key]);
            $criteria = key($this->references[$key]);
        }

        if (!array_key_exists($criteria, $this->references[$key])) {
            throw new ParserException("Undefined reference `{$key}`.`{$criteria}`");
        }

        foreach ($this->references[$key][$criteria] as &$subset) {
            if (isset($subset[$container])) {
                // back reference!
                $data = &$subset[$container];
            } else {
                $subset[$container] = &$data;
            }

            unset($subset);
        }
    }

    /**
     * Mount record data into internal data storage under specified container using reference key
     * (inner key) and reference criteria (outer key value).
     *
     * Example (default ORM Loaders):
     * $this->parent->mountArray('comments', 'id', 1, [
     *      'id' => 100,
     *      'user_id' => 1,
     *      ...
     * ]);
     *
     * In this example "id" argument is inner key of "user" record and it's linked to outer key
     * "user_id" in "profile" record, which defines reference criteria as 1.
     *
     * Add added records will be added as array items.
     *
     * @param string $container
     * @param string $key
     * @param mixed  $criteria
     * @param array  $data
     *
     * @throws ParserException
     */
    final protected function mountArray(string $container, string $key, $criteria, array &$data): void
    {
        if (!array_key_exists($criteria, $this->references[$key])) {
            throw new ParserException("Undefined reference `{$key}`.`{$criteria}`");
        }

        foreach ($this->references[$key][$criteria] as &$subset) {
            if (!in_array($data, $subset[$container])) {
                $subset[$container][] = &$data;
            }

            unset($subset);
            continue;
        }
    }

    /**
     * Register key name which must be aggregated for the further selection.
     *
     * @param string $key
     *
     * @throws ParserException
     */
    final protected function trackReference(string $key): void
    {
        if (!in_array($key, $this->columns)) {
            throw new ParserException(
                "Unable to create reference, key `{$key}` does not exist"
            );
        }

        if (!in_array($key, $this->trackReferences)) {
            $this->trackReferences[] = $key;
        }
    }

    /**
     * Create internal references cache based on requested keys (only for deduplicated data!).
     *
     * For example, if we have request for "id" as reference key, every record will create
     * following structure:
     *
     * $this->references[id][ID_VALUE] = ITEM.
     *
     * @param array $data
     * @see deduplicate()
     */
    final protected function collectReferences(array &$data): void
    {
        foreach ($this->trackReferences as $key) {
            if (!empty($data[$key])) {
                $this->references[$key][$data[$key]][] = &$data;
            }
        }
    }
}
