<?php

use Civi\Test\HeadlessInterface;
use Civi\Test\TransactionalInterface;

abstract class BaseHeadlessTest extends PHPUnit\Framework\TestCase implements
    HeadlessInterface,
    TransactionalInterface {

  public function setUpHeadless() {
    return \Civi\Test::headless()
      ->installMe(__DIR__)
      ->apply();
  }

  public function setUp(): void {
    parent::setUp();
    $ownerOrgId = $this->createCompany(1)['contact_id'];
    $this->updateFinancialAccountOwner('Donation', $ownerOrgId);
    $this->updateFinancialAccountOwner('Member Dues', $ownerOrgId);
    $this->updateFinancialAccountOwner('Campaign Contribution', $ownerOrgId);
    $this->updateFinancialAccountOwner('Event Fee', $ownerOrgId);
  }

  public function createCompany($companyNumber, $alternativeParams = []) {
    $creditNoteTemplateId = \Civi\Api4\MessageTemplate::get()
      ->addSelect('id')
      ->addWhere('msg_title', '=', 'Credit Note Invoice')
      ->execute()
      ->first()['id'];

    $defaultParams = [
      'name' => "testorg{$companyNumber}",
      'invoice_template_id' => 1,
      'invoice_prefix' => "INV{$companyNumber}_",
      'next_invoice_number' => "00000{$companyNumber}",
      'creditnote_prefix' => "CN{$companyNumber}_",
      'next_creditnote_number' => "00000{$companyNumber}",
      'creditnote_template_id' => $creditNoteTemplateId,
    ];

    $params = $defaultParams;
    if (!empty($alternativeParams)) {
      $params = array_merge($defaultParams, $alternativeParams);
    }

    $orgId = civicrm_api3('Contact', 'create', [
      'sequential' => 1,
      'contact_type' => 'Organization',
      'organization_name' => $params['name'],
    ])['id'];
    unset($params['name']);
    $params['contact_id'] = $orgId;

    $company = CRM_Financeextras_BAO_Company::create($params);
    return $company->toArray();
  }

  public function updateFinancialAccountOwner($accountName, $newOwnerId) {
    return civicrm_api3('FinancialAccount', 'get', [
      'sequential' => 1,
      'name' => $accountName,
      'api.FinancialAccount.create' => ['id' => '$value.id', 'contact_id' => $newOwnerId],
    ])['values'][0];
  }

}
