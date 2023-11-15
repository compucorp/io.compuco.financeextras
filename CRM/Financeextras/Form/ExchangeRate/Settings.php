<?php

use CRM_Financeextras_ExtensionUtil as E;

/**
 * Exchange Rate Settings form controller
 */
class CRM_Financeextras_Form_ExchangeRate_Settings extends CRM_Core_Form {

  /**
   * @inheritdoc
   */
  public function buildQuickForm() {
    CRM_Utils_System::setTitle(ts('Currency Exchange Settings'));

    $this->add(
      'checkbox',
      'display_on_invoice',
      ts('Display currency conversion for Tax on invoices')
    );

    $this->addCurrency('sales_tax_currency', ts('Sales Tax Currency'), FALSE);

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

    $elementWithHelpTexts = ['display_on_invoice', 'sales_tax_currency'];

    $this->assign('help', $elementWithHelpTexts);
    $this->assign('elementNames', $this->getRenderableElementNames());
  }

  /**
   * Called after form has been successfully submitted
   */
  public function postProcess() {
    $values = $this->exportValues();

    Civi::settings()->set('fe_exchange_rate_settings', $values);

    CRM_Core_Session::setStatus('Currency Exchange settings saved successfully', '', 'success');
  }

  public function setDefaultValues() {
    return Civi::settings()->get('fe_exchange_rate_settings');
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

}
