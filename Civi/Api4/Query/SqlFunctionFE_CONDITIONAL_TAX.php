<?php

namespace Civi\Api4\Query;

/**
 * Finance Extras: conditional tax amount calculator.
 *
 * Computes the absolute-value tax amount for a row when a tax code field
 * matches a specific value, otherwise zero. Implemented directly in SQL to
 * avoid the per-row Smarty rewrite the SOA Finance report used to rely on.
 *
 * Rendered SQL:
 *   IF(<tax_code_field> = <tax_code_value>, ABS(<amount_field>) * <rate>, 0)
 *
 * Example usage in a SavedSearch select clause:
 *   'FE_CONDITIONAL_TAX(
 *       FinancialItem_EntityFinancialTrxn_FinancialTrxn_01.amount,
 *       ...account_type_code,
 *       "T8",
 *       0.2
 *    ) AS tax_amount'
 *
 * Search Kit's stock ABS/IF/ROUND functions can't express the combination
 * "IF(x = y, ABS(z) * n, 0)" because their allowed param types (`must_be`)
 * forbid nested functions and equations. This class encodes the whole thing
 * in one step.
 *
 * Discovered by Search Kit via Civi\Api4\Utils\CoreUtil::getSqlFunctions(),
 * which scans each active extension for Civi/Api4/Query/SqlFunction*.php.
 * Autoloaded through the extension's PSR-4 classloader mapping `Civi\` to
 * the extension's `Civi/` directory (see info.xml).
 */
class SqlFunctionFE_CONDITIONAL_TAX extends SqlFunction {

  protected static $category = self::CATEGORY_MATH;

  /**
   * Declared as String so the raw DECIMAL value returned by the wrapped
   * MySQL expression (e.g. "3.50") is preserved untouched all the way to
   * the money-formatting pass in
   * SearchDisplayRun::alterFinanceReportDisplay.
   *
   * Two separate pieces of Api4 infrastructure would otherwise mangle the
   * value — neither of them is appropriate for a tax amount:
   *
   *   1. `FormattingUtil::convertDataType()` — runs right after the API
   *      query assembles its rows, and for `dataType = 'Integer'` does
   *      `(int) $value`, which truncates "3.50" to 3. That would silently
   *      drop the pence from every tax line.
   *
   *   2. `AbstractRunAction::formatViewValue()` — invoked when Search Kit
   *      substitutes a column token; for `dataType = 'Float'` it calls
   *      `CRM_Utils_Number::formatLocaleNumeric()`, which inserts the
   *      site-configured thousands separator. "3000.00" becomes
   *      "3,000.00", and PHP's later `(float) "3,000.00"` in the APIWrapper
   *      evaluates to 3.0, losing the thousands.
   *
   * `String` takes both paths through their fall-through branches and
   * returns the value unchanged — the original DECIMAL text is what the
   * downstream money formatter expects.
   *
   * @var string
   */
  protected static $dataType = 'String';

  protected static function params(): array {
    return [
      [
        'optional' => FALSE,
        'must_be' => ['SqlField'],
        'label' => ts('Amount column'),
      ],
      [
        'optional' => FALSE,
        'must_be' => ['SqlField'],
        'label' => ts('Condition column (typically a tax code)'),
      ],
      [
        'optional' => FALSE,
        'must_be' => ['SqlString'],
        'label' => ts('Condition value (e.g. "T8")'),
      ],
      [
        'optional' => FALSE,
        'must_be' => ['SqlNumber'],
        'label' => ts('Rate to multiply amount by (e.g. 0.2 for 20%)'),
      ],
    ];
  }

  /**
   * Rewrite the rendered argument list into the equivalent native SQL.
   *
   * The default renderer would emit `FE_CONDITIONAL_TAX(a , b , c , d)`; we
   * want `IF(b = c, ABS(a) * d, 0)` so that MySQL can execute it.
   */
  public function render(\Civi\Api4\Query\Api4Query $query, bool $includeAlias = FALSE): string {
    $exprs = [];
    foreach ($this->args as $arg) {
      foreach ($arg['expr'] ?? [] as $expr) {
        $exprs[] = $expr->render($query);
      }
    }
    // Expect exactly four rendered arguments.
    if (count($exprs) !== 4) {
      throw new \CRM_Core_Exception('FE_CONDITIONAL_TAX expects exactly 4 arguments.');
    }
    [$amount, $conditionField, $conditionValue, $rate] = $exprs;
    // Wrap the arithmetic in ROUND(..., 2) so the returned value is always
    // formatted to two decimal places and matches the old Smarty rewrite's
    // `format="%.2f"`. The else branch is 0 to keep non-matching rows
    // unambiguous; downstream formatting turns it into "£0.00".
    $sql = "IF($conditionField = $conditionValue, ROUND(ABS($amount) * $rate, 2), 0)";
    return $sql . ($includeAlias ? " AS `{$this->getAlias()}`" : '');
  }

  public static function getTitle(): string {
    return ts('Conditional tax amount');
  }

  public static function getDescription(): string {
    return ts('Absolute amount multiplied by a rate when the condition column matches a value, otherwise zero.');
  }

}
