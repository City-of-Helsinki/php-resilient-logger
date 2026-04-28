<?php

declare(strict_types=1);

namespace ResilientLogger\Utils;

class ReflectHelpers {
  static function isFullyQualifiedClassName(mixed $target): bool {
    return is_string($target) && class_exists($target);
  }

  static function isFreeCallable(mixed $target): bool {
    return $target instanceof \Closure || (is_string($target) && !str_contains($target, '::'));
  }

  static function isArrayCallable(mixed $target): bool {
    return is_array($target) && count($target) === 2;
  }

  static function isStringCallable(mixed $target): bool {
    return is_string($target) && str_contains($target, '::');
  }

  static function createReflection(mixed $target): \ReflectionFunctionAbstract {
    if (self::isFreeCallable($target)) {
      return new \ReflectionFunction($target);
    } 
    
    if (self::isArrayCallable($target)) {
      return new \ReflectionMethod(...$target);
    } 
    
    if (self::isStringCallable($target)) {
      return new \ReflectionMethod($target);
    }

     throw new \InvalidArgumentException("Unsupported callable format.");
  }

  static function createFactory(string|callable|array $target, string $interface): callable {
      if (self::isFullyQualifiedClassName($target)) {
          if (!is_subclass_of($target, $interface)) {
              throw new \TypeError("Class $target does not implement $interface");
          }
          return fn(array $config) => new $target($config);
      }

      // Path B: Callable
      if (is_callable($target)) {
          // Minimal Reflection Check
          $ref = self::createReflection($target);
          $type = $ref->getReturnType();

          // Mechanical Check: Is a return type defined and does it match?
          if (!$type || !($type instanceof \ReflectionNamedType)) {
              throw new \TypeError("Factory must have a named return type hint.");
          }

          if ($type->getName() !== $interface && !is_subclass_of($type->getName(), $interface)) {
              throw new \TypeError("Factory return type {$type->getName()} is not $interface.");
          }

          return $target;
      }

      throw new \InvalidArgumentException("Target is neither a valid FQCN nor a callable.");
  }
}
