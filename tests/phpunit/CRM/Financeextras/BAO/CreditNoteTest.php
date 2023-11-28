<?php

use CRM_Financeextras_BAO_CreditNote as CreditNote;

/**
 * @group headless
 */
class CRM_Financeextras_BAO_CreditNoteTest extends BaseHeadlessTest {

  public function testCreditNoteNumberWillNotBeCalculatedAutomaticallyIfSuppliedAsParameter() {
    $params = [
      'status_id' => 1,
      'contact_id' => 1,
      'owner_organization' => 1,
      'cn_number' => 'DF_5550001',
    ];

    $creditNote = CreditNote::create($params);

    $this->assertEquals($params['cn_number'], $creditNote->cn_number);
  }

  public function testCreditNoteNumberIsSetToCompanyNextCreditNoteNumber() {
    $company = $this->createCompany(1, ['creditnote_prefix' => 'CN_', 'next_creditnote_number' => '00004']);

    $params = [
      'status_id' => 1,
      'contact_id' => 1,
      'owner_organization' => $company['contact_id'],
    ];

    $creditNote = CreditNote::create($params);
    $creditNote2 = CreditNote::create($params);

    $this->assertEquals('CN_00004', $creditNote->cn_number);
    $this->assertEquals('CN_00005', $creditNote2->cn_number);
  }

}
