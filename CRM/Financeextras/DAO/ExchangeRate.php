<?php

/**
 * @package CRM
 * @copyright CiviCRM LLC https://civicrm.org/licensing
 *
 * Generated from io.compuco.financeextras/xml/schema/CRM/Financeextras/ExchangeRate.xml
 * DO NOT EDIT.  Generated by CRM_Core_CodeGen
 * (GenCodeChecksum:5385ce99dc17005c8b0f00f5bb477693)
 */
use CRM_Financeextras_ExtensionUtil as E;

/**
 * Database access object for the ExchangeRate entity.
 */
class CRM_Financeextras_DAO_ExchangeRate extends CRM_Core_DAO {
  const EXT = E::LONG_NAME;
  const TABLE_ADDED = '';

  /**
   * Static instance to hold the table name.
   *
   * @var string
   */
  public static $_tableName = 'financeextras_exchange_rate';

  /**
   * Should CiviCRM log any modifications to this table in the civicrm_log table.
   *
   * @var bool
   */
  public static $_log = TRUE;

  /**
   * Paths for accessing this entity in the UI.
   *
   * @var string[]
   */
  protected static $_paths = [
    'update' => 'civicrm/exchange-rate/add?reset=1&id=[id]',
    'delete' => 'civicrm/exchange-rate/delete?reset=1&id=[id]',
  ];

  /**
   * Unique ExchangeRate ID
   *
   * @var int|string|null
   *   (SQL type: int unsigned)
   *   Note that values will be retrieved from the database as a string.
   */
  public $id;

  /**
   * Exchange rate date
   *
   * @var string|null
   *   (SQL type: date)
   *   Note that values will be retrieved from the database as a string.
   */
  public $exchange_date;

  /**
   * 3 character string, value from config setting or input via user.
   *
   * @var string|null
   *   (SQL type: varchar(3))
   *   Note that values will be retrieved from the database as a string.
   */
  public $base_currency;

  /**
   * 3 character string, value from config setting or input via user.
   *
   * @var string|null
   *   (SQL type: varchar(3))
   *   Note that values will be retrieved from the database as a string.
   */
  public $conversion_currency;

  /**
   * The number of the converted currency to the base currency.
   *
   * @var float|string
   *   (SQL type: decimal(20,2))
   *   Note that values will be retrieved from the database as a string.
   */
  public $base_to_conversion_rate;

  /**
   * The number of the Base currency to the converted currency.
   *
   * @var float|string
   *   (SQL type: decimal(20,2))
   *   Note that values will be retrieved from the database as a string.
   */
  public $conversion_to_base_rate;

  /**
   * Class constructor.
   */
  public function __construct() {
    $this->__table = 'financeextras_exchange_rate';
    parent::__construct();
  }

  /**
   * Returns localized title of this entity.
   *
   * @param bool $plural
   *   Whether to return the plural version of the title.
   */
  public static function getEntityTitle($plural = FALSE) {
    return $plural ? E::ts('Exchange Rates') : E::ts('Exchange Rate');
  }

  /**
   * Returns all the column names of this table
   *
   * @return array
   */
  public static function &fields() {
    if (!isset(Civi::$statics[__CLASS__]['fields'])) {
      Civi::$statics[__CLASS__]['fields'] = [
        'id' => [
          'name' => 'id',
          'type' => CRM_Utils_Type::T_INT,
          'description' => E::ts('Unique ExchangeRate ID'),
          'required' => TRUE,
          'where' => 'financeextras_exchange_rate.id',
          'table_name' => 'financeextras_exchange_rate',
          'entity' => 'ExchangeRate',
          'bao' => 'CRM_Financeextras_DAO_ExchangeRate',
          'localizable' => 0,
          'html' => [
            'type' => 'Number',
          ],
          'readonly' => TRUE,
          'add' => NULL,
        ],
        'exchange_date' => [
          'name' => 'exchange_date',
          'type' => CRM_Utils_Type::T_DATE,
          'title' => E::ts('Exchange Date'),
          'description' => E::ts('Exchange rate date'),
          'where' => 'financeextras_exchange_rate.exchange_date',
          'table_name' => 'financeextras_exchange_rate',
          'entity' => 'ExchangeRate',
          'bao' => 'CRM_Financeextras_DAO_ExchangeRate',
          'localizable' => 0,
          'html' => [
            'type' => 'Select Date',
          ],
          'add' => NULL,
        ],
        'base_currency' => [
          'name' => 'base_currency',
          'type' => CRM_Utils_Type::T_STRING,
          'title' => E::ts('Base Currency'),
          'description' => E::ts('3 character string, value from config setting or input via user.'),
          'maxlength' => 3,
          'size' => CRM_Utils_Type::FOUR,
          'where' => 'financeextras_exchange_rate.base_currency',
          'headerPattern' => '/cur(rency)?/i',
          'dataPattern' => '/^[A-Z]{3}$/',
          'default' => NULL,
          'table_name' => 'financeextras_exchange_rate',
          'entity' => 'ExchangeRate',
          'bao' => 'CRM_Financeextras_DAO_ExchangeRate',
          'localizable' => 0,
          'html' => [
            'type' => 'Select',
          ],
          'pseudoconstant' => [
            'table' => 'civicrm_currency',
            'keyColumn' => 'name',
            'labelColumn' => 'full_name',
            'nameColumn' => 'name',
            'abbrColumn' => 'symbol',
          ],
          'add' => NULL,
        ],
        'conversion_currency' => [
          'name' => 'conversion_currency',
          'type' => CRM_Utils_Type::T_STRING,
          'title' => E::ts('Conversion Currency'),
          'description' => E::ts('3 character string, value from config setting or input via user.'),
          'maxlength' => 3,
          'size' => CRM_Utils_Type::FOUR,
          'where' => 'financeextras_exchange_rate.conversion_currency',
          'headerPattern' => '/cur(rency)?/i',
          'dataPattern' => '/^[A-Z]{3}$/',
          'default' => NULL,
          'table_name' => 'financeextras_exchange_rate',
          'entity' => 'ExchangeRate',
          'bao' => 'CRM_Financeextras_DAO_ExchangeRate',
          'localizable' => 0,
          'html' => [
            'type' => 'Select',
          ],
          'pseudoconstant' => [
            'table' => 'civicrm_currency',
            'keyColumn' => 'name',
            'labelColumn' => 'full_name',
            'nameColumn' => 'name',
            'abbrColumn' => 'symbol',
          ],
          'add' => NULL,
        ],
        'base_to_conversion_rate' => [
          'name' => 'base_to_conversion_rate',
          'type' => CRM_Utils_Type::T_MONEY,
          'title' => E::ts('Base To Conversion Rate'),
          'description' => E::ts('The number of the converted currency to the base currency.'),
          'required' => FALSE,
          'precision' => [
            20,
            2,
          ],
          'where' => 'financeextras_exchange_rate.base_to_conversion_rate',
          'table_name' => 'financeextras_exchange_rate',
          'entity' => 'ExchangeRate',
          'bao' => 'CRM_Financeextras_DAO_ExchangeRate',
          'localizable' => 0,
          'html' => [
            'type' => 'Text',
          ],
          'add' => NULL,
        ],
        'conversion_to_base_rate' => [
          'name' => 'conversion_to_base_rate',
          'type' => CRM_Utils_Type::T_MONEY,
          'title' => E::ts('Conversion To Base Rate'),
          'description' => E::ts('The number of the Base currency to the converted currency.'),
          'required' => FALSE,
          'precision' => [
            20,
            2,
          ],
          'where' => 'financeextras_exchange_rate.conversion_to_base_rate',
          'table_name' => 'financeextras_exchange_rate',
          'entity' => 'ExchangeRate',
          'bao' => 'CRM_Financeextras_DAO_ExchangeRate',
          'localizable' => 0,
          'html' => [
            'type' => 'Text',
          ],
          'add' => NULL,
        ],
      ];
      CRM_Core_DAO_AllCoreTables::invoke(__CLASS__, 'fields_callback', Civi::$statics[__CLASS__]['fields']);
    }
    return Civi::$statics[__CLASS__]['fields'];
  }

  /**
   * Return a mapping from field-name to the corresponding key (as used in fields()).
   *
   * @return array
   *   Array(string $name => string $uniqueName).
   */
  public static function &fieldKeys() {
    if (!isset(Civi::$statics[__CLASS__]['fieldKeys'])) {
      Civi::$statics[__CLASS__]['fieldKeys'] = array_flip(CRM_Utils_Array::collect('name', self::fields()));
    }
    return Civi::$statics[__CLASS__]['fieldKeys'];
  }

  /**
   * Returns the names of this table
   *
   * @return string
   */
  public static function getTableName() {
    return self::$_tableName;
  }

  /**
   * Returns if this table needs to be logged
   *
   * @return bool
   */
  public function getLog() {
    return self::$_log;
  }

  /**
   * Returns the list of fields that can be imported
   *
   * @param bool $prefix
   *
   * @return array
   */
  public static function &import($prefix = FALSE) {
    $r = CRM_Core_DAO_AllCoreTables::getImports(__CLASS__, 'xtras_exchange_rate', $prefix, []);
    return $r;
  }

  /**
   * Returns the list of fields that can be exported
   *
   * @param bool $prefix
   *
   * @return array
   */
  public static function &export($prefix = FALSE) {
    $r = CRM_Core_DAO_AllCoreTables::getExports(__CLASS__, 'xtras_exchange_rate', $prefix, []);
    return $r;
  }

  /**
   * Returns the list of indices
   *
   * @param bool $localize
   *
   * @return array
   */
  public static function indices($localize = TRUE) {
    $indices = [];
    return ($localize && !empty($indices)) ? CRM_Core_DAO_AllCoreTables::multilingualize(__CLASS__, $indices) : $indices;
  }

}
