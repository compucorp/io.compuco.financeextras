<?php

namespace Civi\Financeextras\Hook\alterContent;

use Civi\Financeextras\Setup\Manage\AccountsReceivablePaymentMethod as AccountsReceivablePaymentMethod;
use CRM_Financeextras_ExtensionUtil as ExtensionUtil;

class PaymentStatus {

  private $callerPageTemplate;

  private $callerPageContent;

  /**
   * The pages that are affected
   * by this hook and their templates path.
   */
  const TARGET_PAGES_TEMPLATES = [
    'contribution-form' => 'CRM/Contribute/Page/Tab.tpl',
    'membership-form' => 'CRM/Member/Page/Tab.tpl',
    'event-registration' => 'CRM/Event/Page/Tab.tpl',
  ];

  public function __construct($callerPageTemplate, &$callerPageContent) {
    $this->callerPageTemplate = $callerPageTemplate;
    $this->callerPageContent = &$callerPageContent;
  }

  public function run(): void {
    if (!$this->shouldRun()) {
      return;
    }

    $this->enforceAccountReceivablePaymentMethodOnPendingContribution();
  }

  private function shouldRun(): bool {
    if (in_array($this->callerPageTemplate, self::TARGET_PAGES_TEMPLATES)) {
      return TRUE;
    }

    return FALSE;
  }

  /**
   * Adds JS code that when Pending status for the contribution is selected,
   * defaults the payment method to 'Account Receivable' enforce it
   * at the user level by hiding the field.
   *
   * @return void
   */
  private function enforceAccountReceivablePaymentMethodOnPendingContribution(): void {
    // JS variables are not available on contact page tabs when using something like this:
    // https://docs.civicrm.org/dev/en/latest/framework/resources/#add-javascript-variables, so I had
    // to assign them this way instead.
    $helperVars = "<script>
                     var pendingStatusId = {$this->getPendingStatusId()};
                     var accountsReceivablePaymentMethodId = '{$this->getAccountsReceivablePaymentMethodId()}';
                     var paymentDetailsSectionSelector = '{$this->getPaymentDetailsSectionSelector()}';
                   </script>";
    $this->callerPageContent .= $helperVars;

    $url = ExtensionUtil::url('js/payment_status.js');
    $this->callerPageContent .= "<script src='{$url}'></script>";
  }

  private function getPendingStatusId(): string {
    return civicrm_api3('OptionValue', 'getvalue', [
      'return' => 'value',
      'name' => 'Pending',
      'option_group_id' => 'contribution_status',
    ]);
  }

  private function getAccountsReceivablePaymentMethodId(): string {
    return civicrm_api3('OptionValue', 'getvalue', [
      'return' => 'value',
      'option_group_id' => 'payment_instrument',
      'name' => AccountsReceivablePaymentMethod::NAME,
    ]);
  }

  /**
   * Gets the CSS selector for the payment method section
   * for the page that called this hook. Which will be used
   * in JS to show/hide this section.
   *
   * @return void
   */
  private function getPaymentDetailsSectionSelector(): string {
    $selector = '';
    switch ($this->callerPageTemplate) {
      case self::TARGET_PAGES_TEMPLATES['contribution-form']:
        $selector = '.payment-details_group';
        break;

      case self::TARGET_PAGES_TEMPLATES['membership-form']:
        $selector = '.crm-membership-form-block-payment_instrument_id, .crm-membership-form-block-billing';
        break;

      case self::TARGET_PAGES_TEMPLATES['event-registration']:
        $selector = '.crm-event-eventfees-form-block-payment_instrument_id, #billing-payment-block';
        break;
    }

    return $selector;
  }

}
