<?php

namespace Civi\Api4\Query;

/**
 * Finance Extras: string-typed IF().
 *
 * Identical in SQL semantics to the core `IF(condition, then, else)` function,
 * but its output dataType is explicitly declared as `String`. The distinction
 * matters for Search Kit column rewrites.
 *
 * When Search Kit's AbstractRunAction::replaceTokens substitutes a token such
 * as `[transaction_type_code]` into a rewrite string, it first re-formats the
 * token value through `formatViewValue()`, passing the dataType reported by
 * the underlying SELECT expression. Core `SqlFunctionIF` does NOT declare a
 * static `$dataType`, so `SqlFunction::getRenderedDataType()` falls back to
 * the data_type of the first field appearing inside the function. For the
 * Finance report's Type column the condition is `total_amount >= 0`, so the
 * inherited dataType becomes `Money` — which in turn causes the string values
 * "BR" / "BP" to be run through `\Civi::format()->money()` and rendered as
 * "£0.00" for every row.
 *
 * This class short-circuits that fallback by pinning the dataType to String.
 * Rendered SQL is exactly `IF(...)` — the class name is only a lookup key
 * for Search Kit; the renderExpression override emits the native MySQL
 * function name so MySQL is none the wiser.
 */
class SqlFunctionFE_IF_STRING extends SqlFunction {

  protected static $category = self::CATEGORY_COMPARISON;

  protected static $dataType = 'String';

  protected static function params(): array {
    // Mirrors the signature of core SqlFunctionIF.
    return [
      [
        'optional' => FALSE,
        'must_be' => ['SqlEquation', 'SqlField', 'SqlFunction'],
        'label' => ts('If'),
      ],
      [
        'optional' => FALSE,
        'must_be' => ['SqlField', 'SqlString', 'SqlNumber', 'SqlNull', 'SqlFunction'],
        'label' => ts('Then'),
        'can_be_empty' => TRUE,
      ],
      [
        'optional' => FALSE,
        'must_be' => ['SqlField', 'SqlString', 'SqlNumber', 'SqlNull', 'SqlFunction'],
        'label' => ts('Else'),
        'can_be_empty' => TRUE,
      ],
    ];
  }

  /**
   * Emit the native MySQL function name rather than the class-derived one.
   */
  public static function renderExpression(string $output): string {
    return 'IF(' . $output . ')';
  }

  public static function getTitle(): string {
    return ts('If/Else (string result)');
  }

  public static function getDescription(): string {
    return ts('Like IF(), but with an explicit String output dataType so returned text values are not coerced through Money/Float formatting.');
  }

}
