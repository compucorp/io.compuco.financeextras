<?php

namespace Civi\Financeextras\Hook\AlterMailContent;

/**
 * @file
 *
 * This hook is called when the organisation contribution template is being rendered.
 */
class ContributionTemplate {

  public function __construct(private array &$content) {
  }

  public function handle() {
    $this->replaceWithOrganisationTemplate();
  }

  private function replaceWithOrganisationTemplate() {
    $templateContent = \Civi::cache('session')->get('fe_org_message_template');
    if (empty($templateContent)) {
      return;
    }

    $this->content = json_decode(base64_decode($templateContent), TRUE);
    \Civi::cache('session')->clear('fe_org_message_template');
  }

  public static function shouldHandle($content) {
    return ($content['workflow_name'] ?? NULL) === 'contribution_invoice_receipt';
  }

}
