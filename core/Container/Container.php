<?php

declare(strict_types=1);

namespace Core\Container;

use Core\Contracts\ContainerInterface;
use ReflectionClass;
use ReflectionException;
use ReflectionNamedType;

class Container implements ContainerInterface {
    private array $bindings = [];
    private array $instances = [];

    public function set(string $id, callable|string|object $concrete, bool $singleton = false): void {
        if (is_object($concrete) && !$concrete instanceof \Closure) {
            $this->instances[$id] = $concrete;
            return;
        }

        $this->bindings[$id] = [
            'concrete' => $concrete,
            'singleton' => $singleton
        ];
    }

    public function singleton(string $id, callable|string|object $concrete): void {
        $this->set($id, $concrete, true);
    }

    public function get(string $id): mixed {
        if (isset($this->instances[$id])) {
            return $this->instances[$id];
        }

        if (!$this->has($id)) {
            // Auto-wiring
            if (class_exists($id)) {
                return $this->resolve($id);
            }
            throw new NotFoundException("No binding found for {$id}");
        }

        $binding = $this->bindings[$id];
        $concrete = $binding['concrete'];

        $object = null;
        if ($concrete instanceof \Closure) {
            $object = $concrete($this);
        } elseif (is_string($concrete)) {
            $object = $this->resolve($concrete);
        } else {
            throw new ContainerException("Invalid binding for {$id}");
        }

        if ($binding['singleton']) {
            $this->instances[$id] = $object;
        }

        return $object;
    }

    public function has(string $id): bool {
        return isset($this->bindings[$id]) || isset($this->instances[$id]);
    }

    private function resolve(string $class): object {
        try {
            $reflection = new ReflectionClass($class);
        } catch (ReflectionException $e) {
            throw new ContainerException("Target class [{$class}] does not exist.", 0, $e);
        }

        if (!$reflection->isInstantiable()) {
            throw new ContainerException("Target class [{$class}] is not instantiable.");
        }

        $constructor = $reflection->getConstructor();
        if (is_null($constructor)) {
            return new $class();
        }

        $parameters = $constructor->getParameters();
        $dependencies = [];

        foreach ($parameters as $parameter) {
            $type = $parameter->getType();

            if (!$type instanceof ReflectionNamedType || $type->isBuiltin()) {
                if ($parameter->isDefaultValueAvailable()) {
                    $dependencies[] = $parameter->getDefaultValue();
                } else {
                    throw new ContainerException("Cannot resolve non-class dependency {$parameter->getName()} in {$class}");
                }
            } else {
                $dependencies[] = $this->get($type->getName());
            }
        }

        return $reflection->newInstanceArgs($dependencies);
    }
}
