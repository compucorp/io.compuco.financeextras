<?php

namespace Civi\Financeextras\Hook\Post;

class UpdateLineItemPriceFieldValues {

  public function __construct(
    private string $op,
    private string $objectName,
    private int $objectId,
    private $objectRef,
  ) {
  }

  public function run() {
    $this->updatePriceFieldValues();
  }

  private function updatePriceFieldValues(): void {
    try {
      $record = (array) $this->objectRef;

      if ($this->objectName !== 'LineItem' || !in_array($this->op, ['create', 'edit'])
        || (!in_array($record['price_field_id'], [NULL, 'null']) && !in_array($record['price_field_value_id'], [NULL, 'null']))
        || in_array($record['contribution_id'], [NULL, 'null'])) {
        return;
      }

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

      $lineItem = \Civi\Api4\LineItem::update(FALSE);

      if (in_array($record['price_field_id'], [NULL, 'null'])) {
        $lineItem->addValue('price_field_id', $priceField['id']);
      }
      if (in_array($record['price_field_value_id'], [NULL, 'null'])) {
        $lineItem->addValue('price_field_value_id', $priceFieldValueID);
      }

      $lineItem->addWhere('id', '=', $this->objectId)->execute();

    }
    catch (\Throwable $e) {
    }
  }

}
