<?php

namespace Civi\Api4\Query;

/**
 * Finance Extras: string-typed ABS().
 *
 * Semantically identical to MySQL's native ABS() — the rendered SQL is
 * literally `ABS(...)` — but the output `dataType` is pinned to `String`
 * so that Api4's output-value pipeline leaves the value untouched.
 *
 * Core `SqlFunctionABS` declares `$dataType = 'Integer'`, which causes
 * `FormattingUtil::convertDataType()` to execute `(int) $value` on every
 * row right after the API query. For a DECIMAL value such as "15.50"
 * that truncates to 15, silently losing the pence before the row even
 * reaches Search Kit's display layer. That is fine for integer ID
 * fields (the function's original intended use) but wrong for monetary
 * amounts.
 *
 * This wrapper keeps the MySQL behaviour while ensuring the return
 * value is handed on as an unmodified decimal string, so downstream
 * money-formatting (applied by
 * SearchDisplayRun::alterFinanceReportDisplay for the web display, or
 * written as-is to the xlsx/csv download cell) receives the full
 * precision.
 */
class SqlFunctionFE_ABS_STRING extends SqlFunction {

  protected static $category = self::CATEGORY_MATH;

  protected static $dataType = 'String';

  protected static function params(): array {
    return [
      [
        'optional' => FALSE,
        'must_be' => ['SqlField', 'SqlNumber'],
        'label' => ts('Number'),
      ],
    ];
  }

  /**
   * Emit the native MySQL ABS() rather than the class-derived name.
   */
  public static function renderExpression(string $output): string {
    return 'ABS(' . $output . ')';
  }

  public static function getTitle(): string {
    return ts('Absolute value (string-preserving)');
  }

  public static function getDescription(): string {
    return ts('Absolute value of a number, returned without integer truncation so decimal precision is preserved.');
  }

}
