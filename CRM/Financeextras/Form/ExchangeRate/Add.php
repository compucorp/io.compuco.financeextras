<?php

use CRM_Financeextras_ExtensionUtil as E;

/**
 * Exchange Rate Add form controller
 */
class CRM_Financeextras_Form_ExchangeRate_Add extends CRM_Core_Form {

  public int|null $_id;

  public function preProcess() {
    $this->_id = CRM_Utils_Request::retrieve('id', 'Positive', $this);

    $titlePrefix = 'Add';
    if ($this->_id) {
      $titlePrefix = 'Update';
    }

    $this->setTitle($titlePrefix . ' Exchange Rate Value');
    $url = CRM_Utils_System::url('civicrm/exchange-rate', 'reset=1');
    $session = CRM_Core_Session::singleton();
    $session->replaceUserContext($url);
  }

  /**
   * {@inheritdoc}
   */
  public function buildQuickForm() {
    $this->add(
      'datepicker',
      'exchange_date',
      ts('Date'),
      NULL,
      TRUE,
      ['minDate' => date('Y-m-d'), 'time' => FALSE]
    );

    $this->addCurrency('base_currency', ts('Base Currency'), TRUE);
    $this->addCurrency('conversion_currency', ts('Conversion Currency'), TRUE);

    $this->add(
      'number',
      'base_to_conversion_rate',
      ts('Base to Conversion Rate'),
      ['class' => 'form-control', 'min' => 0, 'step' => 0.01, 'required' => TRUE],
      TRUE
    );

    $this->add(
      'number',
      'conversion_to_base_rate',
      ts('Conversion to Base Rate'),
      ['class' => 'form-control', 'min' => 0, 'step' => 0.01, 'required' => TRUE],
      TRUE
    );

    $this->addButtons([
      [
        'type' => 'submit',
        'name' => E::ts('Save'),
        'isDefault' => TRUE,
      ],
      [
        'type' => 'cancel',
        'name' => E::ts('Cancel'),
        'class' => 'btn-secondary-outline',
      ],
    ]);

    $elementWithHelpTexts = ['base_to_conversion_rate', 'conversion_to_base_rate'];

    $this->assign('help', $elementWithHelpTexts);
    $this->assign('elementNames', $this->getRenderableElementNames());
  }

  /**
   * Called after form has been successfully submitted
   */
  public function postProcess() {
    $values = $this->exportValues();

    if (!empty($this->_id)) {
      $values['id'] = $this->_id;
    }

    if ($this->exchangeRateValueExist($values)) {
      CRM_Core_Session::setStatus('Exchange Rate values exists for the given currencies for same date. Please change the currency or date to proceed', '', 'error');
      return;
    }

    civicrm_api4('ExchangeRate', 'save', [
      'records' => [
        $values,
      ],
    ]);

    $addOrUpdate = !empty($this->_id) ? 'updated' : 'added';
    CRM_Core_Session::setStatus(sprintf('Exchange Rate Value has been %s successfully', $addOrUpdate), '', 'success');

    $url = CRM_Utils_System::url('civicrm/exchange-rate', 'reset=1');
    CRM_Utils_System::redirect($url);
  }

  public function setDefaultValues() {
    if (empty($this->_id)) {
      return [];
    }

    $exchangeRate = \Civi\Api4\ExchangeRate::get(FALSE)
      ->addWhere('id', '=', $this->_id)
      ->setLimit(1)
      ->execute()
      ->first();

    if (empty($exchangeRate)) {
      CRM_Core_Session::setStatus("Exchange Rate Value with ID $this->_id not found", 'failed', 'error');
      CRM_Utils_System::redirect('civicrm/exchange-rate');

      return;
    }

    return $exchangeRate;
  }

  /**
   * Get the fields/elements defined in this form.
   *
   * @return array (string)
   */
  public function getRenderableElementNames() {
    $elementNames = [];
    foreach ($this->_elements as $element) {
      /** @var HTML_QuickForm_Element $element */
      $label = $element->getLabel();
      if (!empty($label)) {
        $elementNames[] = $element->getName();
      }
    }
    return $elementNames;
  }

  /**
   * Checks the excahnge rate value already exists
   *
   * @param array $values
   *  Exchange rate data
   *
   * @return bool
   */
  public function exchangeRateValueExist($values) {
    $exchangeRateQuery = \Civi\Api4\ExchangeRate::get(FALSE)
      ->addWhere('exchange_date', '=', $values['exchange_date'])
      ->addWhere('base_currency', '=', $values['base_currency'])
      ->addWhere('conversion_currency', '=', $values['conversion_currency'])
      ->setLimit(1);

    if (!empty($values['id'])) {
      $exchangeRateQuery->addWhere('id', '!=', $values['id']);
    }

    $exchangeRate = $exchangeRateQuery->execute()
      ->first();

    return !empty($exchangeRate);
  }

}
