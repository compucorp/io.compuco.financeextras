<?php

namespace Civi\Api4\Query;

/**
 * Finance Extras wrapper around MySQL's native DATE_FORMAT().
 *
 * Search Kit core does not ship a DATE_FORMAT function, and the default way to
 * re-format dates from within a Search Display (a Smarty `|date_format` filter
 * in a column rewrite) triggers per-row Smarty compilation, which blows PHP's
 * memory_limit when exporting very large reports.
 *
 * Adding this as a custom SqlFunction lets the date be pre-formatted at the
 * database level, so the column value arrives in the result already formatted
 * and the column config no longer needs a Smarty rewrite.
 *
 * Example usage in a SavedSearch select clause:
 *   'FE_DATE_FORMAT(FinancialItem_EntityFinancialTrxn_FinancialTrxn_01.trxn_date, "%d/%m/%Y") AS formatted_date'
 *
 * Search Kit discovers custom SqlFunction classes by scanning active
 * extensions for files matching `Civi/Api4/Query/SqlFunction*.php` — see
 * Civi\Api4\Utils\CoreUtil::getSqlFunctions().
 */
class SqlFunctionFE_DATE_FORMAT extends SqlFunction {

  protected static $category = self::CATEGORY_DATE;

  protected static $dataType = 'String';

  protected static function params(): array {
    return [
      [
        'optional' => FALSE,
        'must_be' => ['SqlField'],
        'label' => ts('Date column'),
      ],
      [
        'optional' => FALSE,
        'must_be' => ['SqlString'],
        'label' => ts('MySQL format string, e.g. "%d/%m/%Y"'),
      ],
    ];
  }

  /**
   * Render as the MySQL-native DATE_FORMAT() rather than FE_DATE_FORMAT().
   */
  public static function renderExpression(string $output): string {
    return 'DATE_FORMAT(' . $output . ')';
  }

  public static function getTitle(): string {
    return ts('Formatted date');
  }

  public static function getDescription(): string {
    return ts('Format a date/datetime column using a MySQL DATE_FORMAT pattern.');
  }

}
