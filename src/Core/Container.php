<?php

declare(strict_types=1);

namespace StaffHub\Core;

use ReflectionClass;
use ReflectionNamedType;
use RuntimeException;

/**
 * Minimal DI container: shared bindings + reflection-based autowiring.
 */
final class Container
{
    /** @var array<string, callable|object|string> */
    private array $bindings = [];

    /** @var array<string, object> */
    private array $instances = [];

    /**
     * @param callable|object|string $concrete factory, instance, or class name
     */
    public function set(string $id, callable|object|string $concrete, bool $shared = true): void
    {
        $this->bindings[$id] = $concrete;
        if (!$shared) {
            unset($this->instances[$id]);
        }
    }

    public function has(string $id): bool
    {
        return isset($this->bindings[$id]) || isset($this->instances[$id]) || class_exists($id);
    }

    public function get(string $id): mixed
    {
        if (isset($this->instances[$id])) {
            return $this->instances[$id];
        }

        $concrete = $this->bindings[$id] ?? $id;

        if (is_object($concrete) && !($concrete instanceof \Closure)) {
            $this->instances[$id] = $concrete;
            return $concrete;
        }

        if ($concrete instanceof \Closure || (is_string($concrete) && is_callable($concrete) && !class_exists($concrete))) {
            $object = $concrete($this);
            $this->instances[$id] = $object;
            return $object;
        }

        if (is_string($concrete)) {
            $object = $this->build($concrete);
            $this->instances[$id] = $object;
            return $object;
        }

        throw new RuntimeException("Unable to resolve [{$id}] from the container.");
    }

    /**
     * Instantiate a class, resolving constructor type-hints recursively.
     */
    public function make(string $class): object
    {
        return $this->build($class);
    }

    private function build(string $class): object
    {
        if (!class_exists($class)) {
            throw new RuntimeException("Class [{$class}] does not exist.");
        }

        $ref = new ReflectionClass($class);
        $ctor = $ref->getConstructor();

        if ($ctor === null || $ctor->getNumberOfParameters() === 0) {
            return new $class();
        }

        $args = [];
        foreach ($ctor->getParameters() as $param) {
            $type = $param->getType();

            if ($type instanceof ReflectionNamedType && !$type->isBuiltin()) {
                $args[] = $this->get($type->getName());
                continue;
            }

            if ($param->isDefaultValueAvailable()) {
                $args[] = $param->getDefaultValue();
                continue;
            }

            if ($param->allowsNull()) {
                $args[] = null;
                continue;
            }

            throw new RuntimeException(
                "Cannot autowire parameter [\${$param->getName()}] of [{$class}]."
            );
        }

        return $ref->newInstanceArgs($args);
    }
}
