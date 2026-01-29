<?php

declare(strict_types=1);

namespace Cycle\ORM\Select;

use Cycle\ORM\Exception\BuilderException;
use JetBrains\PhpStorm\ExpectedValues;
use Cycle\Database\Driver\Compiler;
use Cycle\Database\Query\SelectQuery;

/**
 * Mocks SelectQuery and automatically resolves identifiers for the loaded relations.
 *
 * @method QueryBuilder distinct()
 * @method QueryBuilder where(...$args);
 * @method QueryBuilder andWhere(...$args);
 * @method QueryBuilder orWhere(...$args);
 * @method QueryBuilder having(...$args);
 * @method QueryBuilder andHaving(...$args);
 * @method QueryBuilder orHaving(...$args);
 * @method QueryBuilder orderBy($expression, $direction = 'ASC');
 * @method QueryBuilder limit(int $limit)
 * @method QueryBuilder offset(int $offset)
 * @method QueryBuilder forUpdate()
 * @method QueryBuilder whereJson(string $path, mixed $value)
 * @method QueryBuilder orWhereJson(string $path, mixed $value)
 * @method QueryBuilder whereJsonContains(string $path, mixed $value, bool $encode = true, bool $validate = true)
 * @method QueryBuilder orWhereJsonContains(string $path, mixed $value, bool $encode = true, bool $validate = true)
 * @method QueryBuilder whereJsonDoesntContain(string $path, mixed $value, bool $encode = true, bool $validate = true)
 * @method QueryBuilder orWhereJsonDoesntContain(string $path, mixed $value, bool $encode = true, bool $validate = true)
 * @method QueryBuilder whereJsonContainsKey(string $path)
 * @method QueryBuilder orWhereJsonContainsKey(string $path)
 * @method QueryBuilder whereJsonDoesntContainKey(string $path)
 * @method QueryBuilder orWhereJsonDoesntContainKey(string $path)
 * @method QueryBuilder whereJsonLength(string $path, int $length, string $operator = '=')
 * @method QueryBuilder orWhereJsonLength(string $path, int $length, string $operator = '=')
 * @method int avg($identifier) Perform aggregation (AVG) based on column or expression value.
 * @method int min($identifier) Perform aggregation (MIN) based on column or expression value.
 * @method int max($identifier) Perform aggregation (MAX) based on column or expression value.
 * @method int sum($identifier) Perform aggregation (SUM) based on column or expression value.
 */
final class QueryBuilder
{
    private ?string $forward = null;

    public function __construct(
        private SelectQuery $query,
        /** @internal */
        private AbstractLoader $loader,
    ) {}

    /**
     * Get currently associated query. Immutable.
     */
    public function getQuery(): ?SelectQuery
    {
        return clone $this->query;
    }

    /**
     * Access to underlying loader. Immutable.
     */
    public function getLoader(): AbstractLoader
    {
        return clone $this->loader;
    }

    /**
     * Select query method prefix for all "where" queries. Can route "where" to "onWhere".
     */
    public function withForward(
        #[ExpectedValues(values: ['where', 'onWhere'])]
        ?string $forward = null,
    ): self {
        $builder = clone $this;
        $builder->forward = $forward;

        return $builder;
    }

    public function withQuery(SelectQuery $query): self
    {
        $builder = clone $this;
        $builder->query = $query;

        return $builder;
    }

    /**
     * Resolve given object.field identifier into proper table alias and column name.
     * Attention, calling this method would not affect loaded relations, you must call with/load directly!
     *
     * Use this method for complex relation queries in combination with Expression()
     *
     * @param bool $autoload If set to true (default) target relation will be automatically loaded.
     *
     * @throws BuilderException
     */
    public function resolve(string $identifier, bool $autoload = true): string
    {
        if ($identifier === '*') {
            return '*';
        }

        if (!\str_contains($identifier, '.')) {
            $current = $this->loader;

            do {
                $column = $current->fieldAlias($identifier);

                // Find an inheritance parent that has this field
                if ($column === null) {
                    $parent = $current->getParentLoader();
                    if ($parent !== null) {
                        $current = $parent;
                        continue;
                    }
                }

                return \sprintf('%s.%s', $current->getAlias(), $column ?? $identifier);
            } while (true);
        }

        $split = \strrpos($identifier, '.');

        $loader = $this->findLoader(\substr($identifier, 0, $split), $autoload);
        if ($loader !== null) {
            $identifier = \substr($identifier, $split + 1);
            return \sprintf(
                '%s.%s',
                $loader->getAlias(),
                $loader->fieldAlias($identifier) ?? $identifier,
            );
        }

        return $identifier;
    }

    /**
     * Join relation without loading it's data.
     */
    public function with(string $relation, array $options = []): self
    {
        $this->loader->loadRelation($relation, $options, true, false);

        return $this;
    }

    /**
     * Forward call to underlying target.
     *
     * @return mixed|SelectQuery
     */
    public function __call(string $func, array $args)
    {
        $result = \call_user_func_array(
            $this->targetFunc($func),
            $this->isJoin($func) ? $args : $this->proxyArgs($args),
        );

        if ($result === $this->query) {
            return $this;
        }

        return $result;
    }

    /**
     * Find loader associated with given entity/relation alias.
     *
     * @param bool $autoload When set to true relation will be automatically loaded.
     */
    private function findLoader(string $name, bool $autoload = true): ?LoaderInterface
    {
        if (\strpos($name, '(')) {
            // expressions are not allowed
            return null;
        }

        if ($name == '' || $name == '@' || $name == $this->loader->getTarget() || $name == $this->loader->getAlias()) {
            return $this->loader;
        }

        $loader = $autoload ? $this->loader : clone $this->loader;

        return $loader->loadRelation($name, [], true);
    }

    /**
     * Replace target where call with another compatible method (for example join or having).
     */
    private function targetFunc(string $call): callable
    {
        if ($this->forward != null) {
            switch (\strtolower($call)) {
                case 'where':
                    $call = $this->forward;
                    break;
                case 'orwhere':
                    $call = 'or' . \ucfirst($this->forward);
                    break;
                case 'andwhere':
                    $call = 'and' . \ucfirst($this->forward);
                    break;
            }
        }

        return [$this->query, $call];
    }

    /**
     * Automatically modify all identifiers to mount table prefix. Provide ability to automatically resolve
     * relations.
     */
    private function proxyArgs(array $args): array
    {
        if (!isset($args[0])) {
            return $args;
        }

        if (\is_string($args[0])) {
            $args[0] = $this->resolve($args[0]);
        }

        if (\is_array($args[0])) {
            $args[0] = $this->walkRecursive($args[0], [$this, 'wrap']);
        }

        if ($args[0] instanceof \Closure) {
            $args[0] = function ($q) use ($args): void {
                $args[0]($this->withQuery($q));
            };
        }

        return $args;
    }

    /**
     * Automatically resolve identifier value or wrap the expression.
     *
     * @param mixed $value
     */
    private function wrap(int|string &$identifier, &$value): void
    {
        if (!\is_numeric($identifier)) {
            $identifier = $this->resolve($identifier);
        }

        if ($value instanceof \Closure) {
            $value = function ($q) use ($value): void {
                $value($this->withQuery($q));
            };
        }
    }

    /**
     * Walk through method arguments using given function.
     */
    private function walkRecursive(array $input, callable $func, bool $complex = false): array
    {
        $result = [];
        foreach ($input as $k => $v) {
            if (\is_array($v)) {
                if (!\is_numeric($k) && \in_array(\strtoupper($k), [Compiler::TOKEN_AND, Compiler::TOKEN_OR], true)) {
                    // complex expression like @OR and @AND
                    $result[$k] = $this->walkRecursive($v, $func, true);
                    continue;
                }

                if ($complex) {
                    $v = $this->walkRecursive($v, $func);
                }
            }

            $func(...[&$k, &$v]);
            $result[$k] = $v;
        }

        return $result;
    }

    private function isJoin(string $method): bool
    {
        return \in_array($method, ['join', 'innerJoin', 'rightJoin', 'leftJoin', 'fullJoin'], true);
    }
}
