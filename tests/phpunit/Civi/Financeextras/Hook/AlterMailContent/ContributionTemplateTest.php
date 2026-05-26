<?php

namespace Civi\Financeextras\Hook\AlterMailContent;

use BaseHeadlessTest;

/**
 * @group headless
 */
class ContributionTemplateTest extends BaseHeadlessTest {

  private const CACHE_KEY = 'fe_org_message_template';

  public function tearDown(): void {
    \Civi::cache('session')->delete(self::CACHE_KEY);
    parent::tearDown();
  }

  public function testHandleReplacesContentWithOrganisationTemplate(): void {
    $fakeContent = ['subject' => 'Test Subject', 'body_html' => '<p>Hello</p>', 'body_text' => 'Hello'];
    \Civi::cache('session')->set(self::CACHE_KEY, base64_encode(json_encode($fakeContent)));

    $content = ['workflow_name' => 'contribution_invoice_receipt', 'subject' => 'Original', 'body_html' => ''];
    $hook = new ContributionTemplate($content);
    $hook->handle();

    $this->assertEquals($fakeContent, $content);
  }

  public function testHandleDeletesOnlyTheTemplateKeyFromSessionCache(): void {
    $fakeContent = ['subject' => 'Test', 'body_html' => '', 'body_text' => ''];
    \Civi::cache('session')->set(self::CACHE_KEY, base64_encode(json_encode($fakeContent)));

    // Store an unrelated key that must survive the call.
    $sentinelKey = 'unrelated_session_key';
    \Civi::cache('session')->set($sentinelKey, 'sentinel_value');

    $content = ['workflow_name' => 'contribution_invoice_receipt'];
    $hook = new ContributionTemplate($content);
    $hook->handle();

    // The template key must be gone.
    $this->assertNull(\Civi::cache('session')->get(self::CACHE_KEY),
      'fe_org_message_template should be deleted from the session cache after handle().'
    );

    // The unrelated key must still be present — clear() would have wiped it.
    $this->assertEquals('sentinel_value', \Civi::cache('session')->get($sentinelKey),
      'handle() must not wipe unrelated session cache entries (regression: clear() vs delete()).'
    );

    \Civi::cache('session')->delete($sentinelKey);
  }

  public function testHandleDoesNothingWhenCacheKeyIsAbsent(): void {
    \Civi::cache('session')->delete(self::CACHE_KEY);

    $originalContent = ['workflow_name' => 'contribution_invoice_receipt', 'subject' => 'Original'];
    $content = $originalContent;
    $hook = new ContributionTemplate($content);
    $hook->handle();

    $this->assertEquals($originalContent, $content);
  }

  public function testShouldHandleReturnsTrueOnlyForInvoiceReceiptWorkflow(): void {
    $this->assertTrue(ContributionTemplate::shouldHandle(['workflow_name' => 'contribution_invoice_receipt']));
    $this->assertFalse(ContributionTemplate::shouldHandle(['workflow_name' => 'contribution_online_receipt']));
    $this->assertFalse(ContributionTemplate::shouldHandle([]));
  }

}
