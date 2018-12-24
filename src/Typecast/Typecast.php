<?php
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */
declare(strict_types=1);

namespace Spiral\Cycle\Typecast;

use Spiral\Cycle\Exception\TypecastException;

final class Typecast
{
    /** @var array */
    private $rules;

    /**
     * @param array $rules
     */
    public function __construct(array $rules)
    {
        $this->rules = $rules;
    }

    /**
     * @param array $values
     * @return array
     *
     * @throws TypecastException
     */
    public function cast(array $values): array
    {
        try {
            foreach ($this->rules as $key => $rule) {
                if (!array_key_exists($key, $values)) {
                    continue;
                }
                
                $values[$key] = call_user_func($rule, $values[$key]);
            }
        } catch (\Throwable $e) {
            throw new TypecastException("Unable to typecast `$key`", $e->getCode(), $e);
        }

        return $values;
    }
}