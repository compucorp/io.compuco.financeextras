<?php

namespace Civi\Financeextras\Hook\Post;

class UpdateLineItemPriceFieldValues {

  public function __construct(private int $objectId) {
  }

  public function run() {
    $this->updatePriceFieldValues();
  }

  private function updatePriceFieldValues(): void {
    try {
      $priceSetId        = \CRM_Core_DAO::getFieldValue('CRM_Price_DAO_PriceSet', 'default_contribution_amount', 'id', 'name');
      $priceSet          = current(\CRM_Price_BAO_PriceSet::getSetDetail($priceSetId));
      $priceField        = NULL;

      foreach ($priceSet['fields'] as $field) {
        if ($field['name'] == 'contribution_amount') {
          $priceField = $field;
          break;
        }
      }
      if (empty($priceField)) {
        return;
      }

      $priceFieldValueID = current($priceField['options'])['id'] ?? NULL;
      if (empty($priceFieldValueID)) {
        return;
      }

      \Civi\Api4\LineItem::update(FALSE)
        ->addValue('price_field_id', $priceField['id'])
        ->addValue('price_field_value_id', $priceFieldValueID)
        ->addWhere('id', '=', $this->objectId)
        ->execute();

    }
    catch (\Throwable $e) {
    }
  }

  public static function shouldRun(string $op, string $objectName, $objectRef): bool {
    $record = (array) $objectRef;

    return !($objectName !== 'LineItem' || !in_array($op, ['create', 'edit'])
      || (!in_array($record['price_field_id'], [NULL, 'null']) && !in_array($record['price_field_value_id'], [NULL, 'null']))
      || in_array($record['contribution_id'], [NULL, 'null']));
  }

}
