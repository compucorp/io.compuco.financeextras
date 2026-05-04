<?php

/**
 * @package CRM
 * @copyright CiviCRM LLC https://civicrm.org/licensing
 *
 * Hand-written DAO for the fake CreditNoteImporter entity.
 */
use CRM_Financeextras_ExtensionUtil as E;

class CRM_Financeextras_DAO_CreditNoteImporter extends CRM_Core_DAO {
  const EXT = E::LONG_NAME;
  const TABLE_ADDED = '';

  /**
   * Static instance to hold the table name.
   *
   * @var string
   */
  public static $_tableName = 'financeextras_credit_note_importer_fake_entity';

  /**
   * No actual database logging happens for this fake entity.
   *
   * @var bool
   */
  public static $_log = FALSE;

  /**
   * @var int|string|null
   */
  public $id;

  /**
   * External identifier used to group CSV rows into a single credit note.
   *
   * @var string|null
   */
  public $credit_note_external_id;

  /**
   * Internal CiviCRM contact ID. Either contact_id or contact_external_id is required.
   *
   * @var int|string|null
   */
  public $contact_id;

  /**
   * External identifier of the contact (civicrm_contact.external_identifier).
   *
   * @var string|null
   */
  public $contact_external_id;

  /**
   * Internal CiviCRM contact ID of the owning organisation.
   *
   * @var int|string|null
   */
  public $owner_organization_id;

  /**
   * External identifier of the owning organisation.
   *
   * @var string|null
   */
  public $owner_organization_external_id;

  /**
   * Optional pre-set credit note number.
   *
   * @var string|null
   */
  public $cn_number;

  /**
   * Credit note date. Defaults to today if empty.
   *
   * @var string|null
   */
  public $date;

  /**
   * Credit note reference.
   *
   * @var string|null
   */
  public $reference;

  /**
   * 3-letter currency code (e.g. USD, GBP).
   *
   * @var string|null
   */
  public $currency;

  /**
   * Credit note description.
   *
   * @var string|null
   */
  public $description;

  /**
   * Credit note comment.
   *
   * @var string|null
   */
  public $comment;

  /**
   * Description of this line item.
   *
   * @var string|null
   */
  public $line_description;

  /**
   * Quantity for this line item. Defaults to 1 if empty.
   *
   * @var float|string|null
   */
  public $line_quantity;

  /**
   * Unit price for this line item.
   *
   * @var float|string|null
   */
  public $line_unit_price;

  /**
   * Name of the financial type for this line item.
   *
   * @var string|null
   */
  public $line_financial_type;

  /**
   * Class constructor.
   */
  public function __construct() {
    $this->__table = self::$_tableName;
    parent::__construct();
  }

  /**
   * Returns localized title of this entity.
   *
   * @param bool $plural
   *   Whether to return the plural version of the title.
   */
  public static function getEntityTitle($plural = FALSE) {
    return $plural ? E::ts('Credit Note Importers') : E::ts('Credit Note Importer');
  }

  /**
   * Returns all the column names of this fake table.
   *
   * The fields listed here drive the CSV import column-mapping UI provided
   * by nz.co.fuzion.csvimport.
   *
   * @return array
   */
  public static function &fields() {
    if (!isset(Civi::$statics[__CLASS__]['fields'])) {
      Civi::$statics[__CLASS__]['fields'] = [
        'id' => self::buildField('id', E::ts('Id'), CRM_Utils_Type::T_INT, [
          'description' => E::ts('Synthetic primary key (unused).'),
          'required' => TRUE,
          'readonly' => TRUE,
        ]),

        // Credit Note fields.
        'credit_note_external_id' => self::buildField('credit_note_external_id', E::ts('Credit Note External Id'), CRM_Utils_Type::T_STRING, [
          'required' => TRUE,
          'description' => E::ts('External identifier used to group CSV rows into a single credit note. Rows sharing the same value are added as additional lines on the same credit note.'),
        ]),
        'contact_id' => self::buildField('contact_id', E::ts('Contact Id'), CRM_Utils_Type::T_INT, [
          'description' => E::ts('Internal CiviCRM contact ID. Either contact_id or contact_external_id is required.'),
        ]),
        'contact_external_id' => self::buildField('contact_external_id', E::ts('Contact External Id'), CRM_Utils_Type::T_STRING, [
          'description' => E::ts('External identifier of the contact. Used to look up contact when contact_id is empty.'),
        ]),
        'owner_organization_id' => self::buildField('owner_organization_id', E::ts('Owner Organization Id'), CRM_Utils_Type::T_INT, [
          'description' => E::ts('Internal CiviCRM contact ID of the owning organisation. Either owner_organization_id or owner_organization_external_id is required.'),
        ]),
        'owner_organization_external_id' => self::buildField('owner_organization_external_id', E::ts('Owner Organization External Id'), CRM_Utils_Type::T_STRING, [
          'description' => E::ts('External identifier of the owning organisation. Used when owner_organization_id is empty.'),
        ]),
        'cn_number' => self::buildField('cn_number', E::ts('Credit Note Number'), CRM_Utils_Type::T_STRING, [
          'description' => E::ts('Optional pre-set credit note number. If empty, the system generates one using the owner organisation prefix.'),
          'maxlength' => 11,
        ]),
        'date' => self::buildField('date', E::ts('Credit Note Date'), CRM_Utils_Type::T_DATE, [
          'description' => E::ts('Defaults to today if empty.'),
        ]),
        'reference' => self::buildField('reference', E::ts('Credit Note Reference'), CRM_Utils_Type::T_STRING, [
          'maxlength' => 11,
        ]),
        'currency' => self::buildField('currency', E::ts('Currency'), CRM_Utils_Type::T_STRING, [
          'required' => TRUE,
          'description' => E::ts('3-letter currency code, e.g. USD or GBP.'),
          'maxlength' => 3,
        ]),
        'description' => self::buildField('description', E::ts('Credit Note Description'), CRM_Utils_Type::T_TEXT),
        'comment' => self::buildField('comment', E::ts('Credit Note Comment'), CRM_Utils_Type::T_TEXT),

        // Line fields.
        'line_description' => self::buildField('line_description', E::ts('Line Description'), CRM_Utils_Type::T_TEXT, [
          'description' => E::ts('Description of this line item.'),
        ]),
        'line_quantity' => self::buildField('line_quantity', E::ts('Line Quantity'), CRM_Utils_Type::T_FLOAT, [
          'description' => E::ts('Defaults to 1 if empty.'),
        ]),
        'line_unit_price' => self::buildField('line_unit_price', E::ts('Line Unit Price'), CRM_Utils_Type::T_MONEY, [
          'required' => TRUE,
          'description' => E::ts('Unit price for this line. Tax is added on top automatically when the financial type has a Sales Tax account configured.'),
        ]),
        'line_financial_type' => self::buildField('line_financial_type', E::ts('Line Financial Type'), CRM_Utils_Type::T_STRING, [
          'required' => TRUE,
          'description' => E::ts('Name of the financial type for this line. The first line of a credit note also determines the credit note level financial type for accounting entries. Tax is derived from this financial type\'s Sales Tax Account relationship.'),
        ]),
      ];
      CRM_Core_DAO_AllCoreTables::invoke(__CLASS__, 'fields_callback', Civi::$statics[__CLASS__]['fields']);
    }
    return Civi::$statics[__CLASS__]['fields'];
  }

  /**
   * Helper to keep the fields() definition compact.
   *
   * @param string $name
   * @param string $title
   * @param int $type
   * @param array $extras
   *
   * @return array
   */
  private static function buildField($name, $title, $type, array $extras = []): array {
    $base = [
      'name' => $name,
      'type' => $type,
      'title' => $title,
      'where' => self::$_tableName . '.' . $name,
      'table_name' => self::$_tableName,
      'entity' => 'CreditNoteImporter',
      'bao' => 'CRM_Financeextras_DAO_CreditNoteImporter',
      'localizable' => 0,
      'html' => [
        'label' => $title,
      ],
      'add' => NULL,
    ];

    if (in_array($type, [CRM_Utils_Type::T_STRING], TRUE)) {
      $base['maxlength'] = $extras['maxlength'] ?? 255;
      $base['size'] = CRM_Utils_Type::HUGE;
    }
    if ($type === CRM_Utils_Type::T_MONEY) {
      $base['precision'] = [20, 2];
    }

    unset($extras['maxlength']);

    return array_merge($base, $extras);
  }

  /**
   * @return array
   */
  public static function &fieldKeys() {
    if (!isset(Civi::$statics[__CLASS__]['fieldKeys'])) {
      Civi::$statics[__CLASS__]['fieldKeys'] = array_flip(CRM_Utils_Array::collect('name', self::fields()));
    }
    return Civi::$statics[__CLASS__]['fieldKeys'];
  }

  /**
   * @return string
   */
  public static function getTableName() {
    return self::$_tableName;
  }

  /**
   * @return bool
   */
  public function getLog() {
    return self::$_log;
  }

  /**
   * @param bool $prefix
   *
   * @return array
   */
  public static function &import($prefix = FALSE) {
    $r = CRM_Core_DAO_AllCoreTables::getImports(__CLASS__, 'credit_note_importer_fake_entity', $prefix, []);
    return $r;
  }

  /**
   * @param bool $prefix
   *
   * @return array
   */
  public static function &export($prefix = FALSE) {
    $r = CRM_Core_DAO_AllCoreTables::getExports(__CLASS__, 'credit_note_importer_fake_entity', $prefix, []);
    return $r;
  }

  /**
   * @param bool $localize
   *
   * @return array
   */
  public static function indices($localize = TRUE) {
    return [];
  }

}
