<?php

/**
 * @package CRM
 * @copyright CiviCRM LLC https://civicrm.org/licensing
 *
 * Generated from io.compuco.financeextras/xml/schema/CRM/Financeextras/CreditNote.xml
 * DO NOT EDIT.  Generated by CRM_Core_CodeGen
 * (GenCodeChecksum:63fb7b02badcee46b63bf5f541ae5aa3)
 */
use CRM_Financeextras_ExtensionUtil as E;

/**
 * Database access object for the CreditNote entity.
 */
class CRM_Financeextras_DAO_CreditNote extends CRM_Core_DAO {
  const EXT = E::LONG_NAME;
  const TABLE_ADDED = '';

  /**
   * Static instance to hold the table name.
   *
   * @var string
   */
  public static $_tableName = 'financeextras_credit_note';

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
    'view' => 'civicrm/contribution/creditnote/view?reset=1&id=[id]&action=view',
    'update' => 'civicrm/contribution/creditnote/new?reset=1&id=[id]&action=update',
    'delete' => 'civicrm/contribution/creditnote/delete?reset=1&id=[id]',
  ];

  /**
   * Unique CreditNote ID
   *
   * @var int|string|null
   *   (SQL type: int unsigned)
   *   Note that values will be retrieved from the database as a string.
   */
  public $id;

  /**
   * FK to Contact
   *
   * @var int|string|null
   *   (SQL type: int unsigned)
   *   Note that values will be retrieved from the database as a string.
   */
  public $contact_id;

  /**
   * @var string|null
   *   (SQL type: varchar(11))
   *   Note that values will be retrieved from the database as a string.
   */
  public $cn_number;

  /**
   * Credit Note date
   *
   * @var string|null
   *   (SQL type: date)
   *   Note that values will be retrieved from the database as a string.
   */
  public $date;

  /**
   * One of the values of the financeextras_credit_note_status option group
   *
   * @var int|string
   *   (SQL type: int unsigned)
   *   Note that values will be retrieved from the database as a string.
   */
  public $status_id;

  /**
   * @var string|null
   *   (SQL type: varchar(11))
   *   Note that values will be retrieved from the database as a string.
   */
  public $reference;

  /**
   * 3 character string, value from config setting or input via user.
   *
   * @var string|null
   *   (SQL type: varchar(3))
   *   Note that values will be retrieved from the database as a string.
   */
  public $currency;

  /**
   * Credit note description
   *
   * @var string
   *   (SQL type: text)
   *   Note that values will be retrieved from the database as a string.
   */
  public $description;

  /**
   * Credit note comment
   *
   * @var string
   *   (SQL type: text)
   *   Note that values will be retrieved from the database as a string.
   */
  public $comment;

  /**
   * Total of all the total price fields
   *
   * @var float|string
   *   (SQL type: decimal(20,2))
   *   Note that values will be retrieved from the database as a string.
   */
  public $subtotal;

  /**
   * Credit note sales tax total
   *
   * @var float|string
   *   (SQL type: decimal(20,2))
   *   Note that values will be retrieved from the database as a string.
   */
  public $sales_tax;

  /**
   * Total value of the credit note
   *
   * @var float|string
   *   (SQL type: decimal(20,2))
   *   Note that values will be retrieved from the database as a string.
   */
  public $total_credit;

  /**
   * Class constructor.
   */
  public function __construct() {
    $this->__table = 'financeextras_credit_note';
    parent::__construct();
  }

  /**
   * Returns localized title of this entity.
   *
   * @param bool $plural
   *   Whether to return the plural version of the title.
   */
  public static function getEntityTitle($plural = FALSE) {
    return $plural ? E::ts('Credit Notes') : E::ts('Credit Note');
  }

  /**
   * Returns foreign keys and entity references.
   *
   * @return array
   *   [CRM_Core_Reference_Interface]
   */
  public static function getReferenceColumns() {
    if (!isset(Civi::$statics[__CLASS__]['links'])) {
      Civi::$statics[__CLASS__]['links'] = static::createReferenceColumns(__CLASS__);
      Civi::$statics[__CLASS__]['links'][] = new CRM_Core_Reference_Basic(self::getTableName(), 'contact_id', 'civicrm_contact', 'id');
      CRM_Core_DAO_AllCoreTables::invoke(__CLASS__, 'links_callback', Civi::$statics[__CLASS__]['links']);
    }
    return Civi::$statics[__CLASS__]['links'];
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
          'description' => E::ts('Unique CreditNote ID'),
          'required' => TRUE,
          'where' => 'financeextras_credit_note.id',
          'table_name' => 'financeextras_credit_note',
          'entity' => 'CreditNote',
          'bao' => 'CRM_Financeextras_DAO_CreditNote',
          'localizable' => 0,
          'html' => [
            'type' => 'Number',
          ],
          'readonly' => TRUE,
          'add' => NULL,
        ],
        'contact_id' => [
          'name' => 'contact_id',
          'type' => CRM_Utils_Type::T_INT,
          'description' => E::ts('FK to Contact'),
          'where' => 'financeextras_credit_note.contact_id',
          'table_name' => 'financeextras_credit_note',
          'entity' => 'CreditNote',
          'bao' => 'CRM_Financeextras_DAO_CreditNote',
          'localizable' => 0,
          'FKClassName' => 'CRM_Contact_DAO_Contact',
          'add' => NULL,
        ],
        'cn_number' => [
          'name' => 'cn_number',
          'type' => CRM_Utils_Type::T_STRING,
          'title' => E::ts('Autogenerated credit note number'),
          'maxlength' => 11,
          'size' => CRM_Utils_Type::TWELVE,
          'where' => 'financeextras_credit_note.cn_number',
          'table_name' => 'financeextras_credit_note',
          'entity' => 'CreditNote',
          'bao' => 'CRM_Financeextras_DAO_CreditNote',
          'localizable' => 0,
          'html' => [
            'type' => 'Text',
          ],
          'add' => NULL,
        ],
        'date' => [
          'name' => 'date',
          'type' => CRM_Utils_Type::T_DATE,
          'title' => E::ts('Date'),
          'description' => E::ts('Credit Note date'),
          'where' => 'financeextras_credit_note.date',
          'table_name' => 'financeextras_credit_note',
          'entity' => 'CreditNote',
          'bao' => 'CRM_Financeextras_DAO_CreditNote',
          'localizable' => 0,
          'html' => [
            'type' => 'Select Date',
          ],
          'add' => NULL,
        ],
        'status_id' => [
          'name' => 'status_id',
          'type' => CRM_Utils_Type::T_INT,
          'description' => E::ts('One of the values of the financeextras_credit_note_status option group'),
          'required' => TRUE,
          'where' => 'financeextras_credit_note.status_id',
          'table_name' => 'financeextras_credit_note',
          'entity' => 'CreditNote',
          'bao' => 'CRM_Financeextras_DAO_CreditNote',
          'localizable' => 0,
          'html' => [
            'type' => 'Select',
            'label' => E::ts("Status"),
          ],
          'pseudoconstant' => [
            'optionGroupName' => 'financeextras_credit_note_status',
            'optionEditPath' => 'civicrm/admin/options/financeextras_credit_note_status',
          ],
          'add' => NULL,
        ],
        'reference' => [
          'name' => 'reference',
          'type' => CRM_Utils_Type::T_STRING,
          'title' => E::ts('Credit Note Reference'),
          'maxlength' => 11,
          'size' => CRM_Utils_Type::TWELVE,
          'where' => 'financeextras_credit_note.reference',
          'table_name' => 'financeextras_credit_note',
          'entity' => 'CreditNote',
          'bao' => 'CRM_Financeextras_DAO_CreditNote',
          'localizable' => 0,
          'html' => [
            'type' => 'Text',
          ],
          'add' => NULL,
        ],
        'currency' => [
          'name' => 'currency',
          'type' => CRM_Utils_Type::T_STRING,
          'title' => E::ts('Financial Currency'),
          'description' => E::ts('3 character string, value from config setting or input via user.'),
          'maxlength' => 3,
          'size' => CRM_Utils_Type::FOUR,
          'where' => 'financeextras_credit_note.currency',
          'headerPattern' => '/cur(rency)?/i',
          'dataPattern' => '/^[A-Z]{3}$/',
          'default' => NULL,
          'table_name' => 'financeextras_credit_note',
          'entity' => 'CreditNote',
          'bao' => 'CRM_Financeextras_DAO_CreditNote',
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
        'description' => [
          'name' => 'description',
          'type' => CRM_Utils_Type::T_TEXT,
          'title' => E::ts('Description'),
          'description' => E::ts('Credit note description'),
          'required' => FALSE,
          'where' => 'financeextras_credit_note.description',
          'table_name' => 'financeextras_credit_note',
          'entity' => 'CreditNote',
          'bao' => 'CRM_Financeextras_DAO_CreditNote',
          'localizable' => 0,
          'html' => [
            'type' => 'TextArea',
            'label' => E::ts("Description"),
          ],
          'add' => NULL,
        ],
        'comment' => [
          'name' => 'comment',
          'type' => CRM_Utils_Type::T_TEXT,
          'title' => E::ts('Comment'),
          'description' => E::ts('Credit note comment'),
          'required' => FALSE,
          'where' => 'financeextras_credit_note.comment',
          'table_name' => 'financeextras_credit_note',
          'entity' => 'CreditNote',
          'bao' => 'CRM_Financeextras_DAO_CreditNote',
          'localizable' => 0,
          'html' => [
            'type' => 'TextArea',
            'label' => E::ts("Comment"),
          ],
          'add' => NULL,
        ],
        'subtotal' => [
          'name' => 'subtotal',
          'type' => CRM_Utils_Type::T_MONEY,
          'title' => E::ts('Subtotal'),
          'description' => E::ts('Total of all the total price fields'),
          'required' => FALSE,
          'precision' => [
            20,
            2,
          ],
          'where' => 'financeextras_credit_note.subtotal',
          'table_name' => 'financeextras_credit_note',
          'entity' => 'CreditNote',
          'bao' => 'CRM_Financeextras_DAO_CreditNote',
          'localizable' => 0,
          'html' => [
            'type' => 'Text',
          ],
          'add' => NULL,
        ],
        'sales_tax' => [
          'name' => 'sales_tax',
          'type' => CRM_Utils_Type::T_MONEY,
          'title' => E::ts('Sales Tax'),
          'description' => E::ts('Credit note sales tax total'),
          'required' => FALSE,
          'precision' => [
            20,
            2,
          ],
          'where' => 'financeextras_credit_note.sales_tax',
          'table_name' => 'financeextras_credit_note',
          'entity' => 'CreditNote',
          'bao' => 'CRM_Financeextras_DAO_CreditNote',
          'localizable' => 0,
          'html' => [
            'type' => 'Text',
          ],
          'add' => NULL,
        ],
        'total_credit' => [
          'name' => 'total_credit',
          'type' => CRM_Utils_Type::T_MONEY,
          'title' => E::ts('Total Credit'),
          'description' => E::ts('Total value of the credit note'),
          'required' => FALSE,
          'precision' => [
            20,
            2,
          ],
          'where' => 'financeextras_credit_note.total_credit',
          'table_name' => 'financeextras_credit_note',
          'entity' => 'CreditNote',
          'bao' => 'CRM_Financeextras_DAO_CreditNote',
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
    $r = CRM_Core_DAO_AllCoreTables::getImports(__CLASS__, 'xtras_credit_note', $prefix, []);
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
    $r = CRM_Core_DAO_AllCoreTables::getExports(__CLASS__, 'xtras_credit_note', $prefix, []);
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
