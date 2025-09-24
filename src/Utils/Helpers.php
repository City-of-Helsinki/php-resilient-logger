<?php

declare(strict_types=1);

namespace ResilientLogger\Utils;

use ResilientLogger\Exceptions\MissingContextException;

class Helpers {
  /**
   * Creates a deeply sorted copy of a mixed data type (array or object).
   *
   * This function recursively traverses the input data. If the input is an object,
   * it is first cast to an array. The function then sorts all nested arrays and
   * the top-level array by their keys in a stable, consistent manner.
   *
   * @param mixed $data The input data to be sorted. This can be an array, an object, or a scalar.
   * @return mixed A new, deeply sorted copy of the input data.
   */
  static function createDeepSortedCopy(mixed $data): mixed {
    if (is_object($data)) {
      $data = (array) $data;
    }

    if (is_array($data)) {
      $sorted = [];

      foreach ($data as $key => $value) {
        $sorted[$key] = self::createDeepSortedCopy($value);
      }

      ksort($sorted);
      return $sorted;
    }

    return $data;
  }

  /**
   * Calculates a stable content based of the input data.
   * The contents is first sorted and the sorted data is then hashed.
   * 
   * @param mixed $contents Input contents to be hashed
   * @return string Stable content hash
   */
  static function contentHash(mixed $contents): string {
    $stringified = json_encode(self::createDeepSortedCopy($contents));
    return hash('sha256', $stringified);
  }

  /**
   * @param array<mixed> $extra
   *   Associative array of logger extras to be checked for keys
   * @param array<string> $requiredFields
   *   List of required fields that must exist in $extra
   * 
   * @throws MissingContextException
   */
  static function assertRequiredExtras(array $extra, array $requiredFields) {
    /** @var array<string> $missingFields */
    $missingFields = [];

    foreach ($requiredFields as $requiredField) {
      if (!array_key_exists($requiredField, $extra)) {
        $missingFields[] = $requiredField;
      }
    }

    if ($missingFields) {
      throw new MissingContextException($missingFields);
    }
  }

  /**
   * Merges a user-provided options array with a set of default options.
   *
   * Keys from the $options array will override the corresponding keys in
   * $defaultOptions. The resulting array will contain all keys from
   * $defaultOptions, with user-defined values where provided.
   *
   * @template T of array
   * @param array<string, mixed> $options
   * @param T $defaultOptions
   * @return T
   */
  static function mergeOptions(array $options, array $defaultOptions): array {
      $merged = array_merge($defaultOptions, $options);
      $filtered = array_intersect_key($merged, $defaultOptions);

      return $filtered;
  }

  /**
   * Ensures the input value is always returned as an array.
   *
   * If a string is provided, it will be wrapped in a new array with the key 'value'.
   * For example, `"hello"` becomes `["value" => "hello"]`. If an array is provided, it's returned as is.
   *
   * @param string|array $value The input value, which can be a string or an array.
   * @return array The input value guaranteed to be an array.
   */
  static function valueAsArray(mixed $value): array {
    if (is_string($value)) {
      return ["value" => $value];
    }

    if (is_array($value)) {
      return $value;
    }

    $valueType = gettype($value);

    throw new \UnexpectedValueException(
        "Invalid value_as_dict input. Expected 'string | array', got '{$valueType}'"
    );
  }
}

?>
