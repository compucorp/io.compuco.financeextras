<?php

namespace Civi\Financeextras\Hook\AlterMailParams;

/**
 * Restores the Smarty variables CiviCRM core stopped assigning to the
 * invoice template.
 *
 * Up to CiviCRM 6.4 CRM_Contribute_Form_Task_Invoice::printPDF() assigned
 * these variables to the 'contribution_invoice_receipt' message template.
 * Later versions removed them and expect templates to use tokens instead.
 * Core does not rewrite templates edited by a site, so those keep using
 * the old variables and print blank values (and a credit note block on
 * every invoice, since the status ids they compare are both unset).
 *
 * Each variable is computed the way core 6.4.1 computed it, and is only
 * added when the caller has not already assigned it. On versions of core
 * that still assign them this class therefore changes nothing.
 */
class InvoiceLegacyVariables {

  /**
   * Variables core 6.4.1 assigned and later versions do not.
   */
  const VARIABLES = [
    'source',
    'invoice_number',
    'invoice_id',
    'defaultCurrency',
    'amount',
    'amountDue',
    'amountPaid',
    'invoice_date',
    'lineItem',
    'refundedStatusId',
    'pendingStatusId',
    'cancelledStatusId',
    'contribution_status_id',
    'contributionStatusName',
    'subTotal',
    'is_pay_later',
    'domain_street_address',
    'domain_supplemental_address_1',
    'domain_supplemental_address_2',
    'domain_supplemental_address_3',
    'domain_city',
    'domain_postal_code',
    'domain_state',
    'domain_country',
    'domain_email',
    'domain_phone',
  ];

  private $templateParams;

  public function __construct(&$templateParams, $context) {
    $this->templateParams = &$templateParams;
  }

  /**
   * Determines if the hook will run.
   *
   * @param array $params
   *   Mail parameters.
   * @param string $context
   *   Mail context.
   *
   * @return bool
   *   returns TRUE if hook should run, FALSE otherwise.
   */
  public static function shouldHandle($params, $context) {
    $workflow = $params['workflow'] ?? $params['valueName'] ?? NULL;

    return $workflow === 'contribution_invoice_receipt';
  }

  public function handle() {
    $tplParams = $this->templateParams['tplParams'] ?? [];
    if (!is_array($tplParams)) {
      return;
    }

    // array_key_exists and not isset: core assigns some of these as NULL.
    $missing = array_values(array_filter(self::VARIABLES, function ($name) use ($tplParams) {
      return !array_key_exists($name, $tplParams);
    }));
    if (empty($missing)) {
      return;
    }

    $contributionId = $tplParams['id']
      ?? $tplParams['contributionID']
      ?? $this->templateParams['tokenContext']['contributionId']
      ?? NULL;
    if (empty($contributionId) || !is_numeric($contributionId)) {
      return;
    }

    $contribution = new \CRM_Contribute_BAO_Contribution();
    $contribution->id = (int) $contributionId;
    if (!$contribution->find(TRUE)) {
      return;
    }

    $values = $this->getContributionVariables($contribution);
    if (array_intersect(['refundedStatusId', 'pendingStatusId', 'cancelledStatusId'], $missing)) {
      $values += $this->getStatusVariables();
    }
    if (array_intersect(['amountPaid', 'amountDue'], $missing)) {
      $values += $this->getPaymentVariables($contribution);
    }
    if (array_intersect(['lineItem', 'subTotal'], $missing)) {
      $values += $this->getLineItemVariables($contribution);
    }
    if (preg_grep('/^domain_/', $missing)) {
      $values += $this->getDomainVariables();
    }

    foreach ($missing as $name) {
      $this->templateParams['tplParams'][$name] = $values[$name] ?? NULL;
    }
  }

  /**
   * @param \CRM_Contribute_BAO_Contribution $contribution
   *
   * @return array
   */
  private function getContributionVariables($contribution) {
    $invoiceNumber = $contribution->invoice_number;
    if (!$invoiceNumber) {
      $invoiceNumber = \CRM_Contribute_BAO_Contribution::getInvoiceNumber((int) $contribution->id);
    }

    return [
      'source' => $contribution->source,
      'invoice_number' => $invoiceNumber,
      'invoice_id' => $contribution->invoice_id,
      'defaultCurrency' => \CRM_Core_Config::singleton()->defaultCurrency,
      'amount' => $contribution->total_amount,
      'invoice_date' => date('F j, Y'),
      'contribution_status_id' => $contribution->contribution_status_id,
      'contributionStatusName' => \CRM_Core_PseudoConstant::getName('CRM_Contribute_BAO_Contribution', 'contribution_status_id', $contribution->contribution_status_id),
      'is_pay_later' => $contribution->is_pay_later,
    ];
  }

  /**
   * @return array
   */
  private function getStatusVariables() {
    $statuses = \CRM_Contribute_PseudoConstant::contributionStatus(NULL, 'name');

    return [
      'refundedStatusId' => \CRM_Utils_Array::key('Refunded', $statuses),
      'pendingStatusId' => \CRM_Utils_Array::key('Pending', $statuses),
      'cancelledStatusId' => \CRM_Utils_Array::key('Cancelled', $statuses),
    ];
  }

  /**
   * @param \CRM_Contribute_BAO_Contribution $contribution
   *
   * @return array
   */
  private function getPaymentVariables($contribution) {
    $amountPaid = \CRM_Core_BAO_FinancialTrxn::getTotalPayments($contribution->id, TRUE);

    return [
      'amountPaid' => $amountPaid,
      'amountDue' => $contribution->total_amount - $amountPaid,
    ];
  }

  /**
   * @param \CRM_Contribute_BAO_Contribution $contribution
   *
   * @return array
   */
  private function getLineItemVariables($contribution) {
    $lineItems = \CRM_Price_BAO_LineItem::getLineItemsByContributionID($contribution->id);
    $subTotal = 0;
    foreach ($lineItems as $lineItem) {
      $subTotal += $lineItem['subTotal'] ?? 0;
    }

    return [
      'lineItem' => $lineItems,
      'subTotal' => $subTotal,
    ];
  }

  /**
   * Gets the address, email and phone of the domain organisation.
   *
   * @return array
   */
  private function getDomainVariables() {
    $domain = \CRM_Core_BAO_Domain::getDomain();
    $location = \Civi\Financeextras\Utils\LocationUtils::getValues((int) $domain->contact_id);
    $address = $location['address'][1] ?? [];

    return [
      'domain_street_address' => $address['street_address'] ?? NULL,
      'domain_supplemental_address_1' => $address['supplemental_address_1'] ?? NULL,
      'domain_supplemental_address_2' => $address['supplemental_address_2'] ?? NULL,
      'domain_supplemental_address_3' => $address['supplemental_address_3'] ?? NULL,
      'domain_city' => $address['city'] ?? NULL,
      'domain_postal_code' => $address['postal_code'] ?? NULL,
      'domain_state' => isset($address['state_province_id']) ? \CRM_Core_PseudoConstant::stateProvinceAbbreviation($address['state_province_id']) : '',
      'domain_country' => isset($address['country_id']) ? \CRM_Core_PseudoConstant::country($address['country_id']) : '',
      'domain_email' => $location['email'][1]['email'] ?? NULL,
      'domain_phone' => $location['phone'][1]['phone'] ?? NULL,
    ];
  }

}
